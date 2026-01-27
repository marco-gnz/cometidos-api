<?php

namespace App\Console\Commands;

use App\Events\ProcesoRendicionGastoStatus;
use App\Models\EstadoProcesoRendicionGasto;
use App\Models\ProcesoRendicionGasto;
use App\Models\RendicionGasto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReasignarRendicionesErrorServidor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:reasignar-rendiciones-error-servidor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reasignar rendiciones por error en servidor.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $total = 0;
            $estados = [
                EstadoProcesoRendicionGasto::STATUS_APROBADO_N,
                EstadoProcesoRendicionGasto::STATUS_APROBADO_S,
                EstadoProcesoRendicionGasto::STATUS_ANULADO
            ];
            ProcesoRendicionGasto::query()
                ->whereNotIn('status', $estados)
                ->where('posicion_firma_ok', '>', 0)
                ->whereHas('documentos')
                ->with(['documentos:id,proceso_rendicion_gasto_id,url'])
                ->chunkById(400, function ($rendiciones) use (&$total) {
                    foreach ($rendiciones as $proceso_rendicion_gasto) {

                        $faltaAlguno = $proceso_rendicion_gasto->documentos->contains(function ($doc) {
                            return !Storage::disk('public')->exists($doc->url);
                        });

                        if ($faltaAlguno) {
                            $observacion = "GECOM: Debido a un inconveniente técnico en el servidor, esta rendición de gastos ha sido reasignada nuevamente a su usuario.
                            Le solicitamos, por favor, editar la rendición de gastos y adjuntar nuevamente los archivos correspondientes.";

                            $estado = [
                                'observacion'           => $observacion,
                                'status'                => EstadoProcesoRendicionGasto::STATUS_RECHAZADO,
                                'p_rendicion_gasto_id'  => $proceso_rendicion_gasto->id,
                                'role_id'               => null,
                                'posicion_firma'        => 0,
                                'is_subrogante'         => false
                            ];

                            $status = EstadoProcesoRendicionGasto::create($estado);

                            if ($status) {
                                $total++;
                                $proceso_rendicion_gasto->update([
                                    'posicion_firma_ok' =>  0,
                                    'fecha_last_firma'  => now()
                                ]);

                                $rendiciones = $proceso_rendicion_gasto->rendiciones()
                                    ->where('rinde_gasto', true)
                                    ->where('last_status', '!=', RendicionGasto::STATUS_PENDIENTE)
                                    ->get();

                                if (count($rendiciones) > 0) {
                                    foreach ($rendiciones as $rendicion) {
                                        $rendicion->update([
                                            'mount_real'    => $rendicion->mount,
                                            'last_status'   => RendicionGasto::STATUS_PENDIENTE
                                        ]);
                                    }
                                }
                                $proceso_rendicion_gasto = $proceso_rendicion_gasto->fresh();

                                $last_status = $proceso_rendicion_gasto->estados()->orderBy('id', 'DESC')->first();
                                ProcesoRendicionGastoStatus::dispatch($last_status);
                            }
                        }
                    }
                });

            $this->info("Comando ejecutado correctamente.");
            $this->info("$total rendiciones actualizadas.");
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
