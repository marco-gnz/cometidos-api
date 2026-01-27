<?php

namespace App\Console\Commands;

use App\Events\SolicitudChangeStatus;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReasignarCometidosErrorServidor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:reasignar-cometidos-error-servidor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reasignar cometidos por error en servidor.';

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
            Solicitud::query()
                ->where('derecho_pago', true)
                ->where('status', Solicitud::STATUS_EN_PROCESO)
                ->where('posicion_firma_ok', '>', 0)
                ->whereHas('documentos') // solo solicitudes con documentos
                ->with(['documentos:id,solicitud_id,url']) // evita N+1 y trae solo columnas necesarias
                ->chunkById(400, function ($solicitudes) use (&$total) {
                    foreach ($solicitudes as $solicitud) {

                        $faltaAlguno = $solicitud->documentos->contains(function ($doc) {
                            return !Storage::disk('public')->exists($doc->url);
                        });

                        if ($faltaAlguno) {
                            $observacion = "GECOM: Debido a un inconveniente técnico en el servidor, esta solicitud ha sido reasignada nuevamente a su usuario. Le solicitamos, por favor, editar la solicitud y adjuntar nuevamente los archivos correspondientes.";
                            $firma_disponible   = $solicitud->firmantes()
                                ->where('posicion_firma', 0)
                                ->where('user_id', $solicitud->user_id)
                                ->first();
                            $status     = EstadoSolicitud::STATUS_PENDIENTE;
                            $estados[]  = [
                                'status'                    => $status,
                                'is_reasignado'             => true,
                                'r_s_firmante_id'           => $firma_disponible ? $firma_disponible->id : null,
                                'posicion_firma_r_s'        => $firma_disponible ? $firma_disponible->posicion_firma : null,
                                'posicion_firma_s'          => $firma_disponible ? $firma_disponible->posicion_firma : null,
                                'solicitud_id'              => $solicitud->id,
                                'posicion_firma'            => $firma_disponible ? $firma_disponible->posicion_firma : null,
                                's_firmante_id'             => $firma_disponible ? $firma_disponible->id : null,
                                'observacion'               => $observacion,
                                'user_id'                   => NULL,
                                'movimiento_system'         => true
                            ];
                            $create_status   = $solicitud->addEstados($estados);

                            if ($create_status) {
                                $estados = [];
                                $solicitud      = $solicitud->fresh();
                                $emails_copy    = [];

                                $last_status = $solicitud->estados()->orderBy('id', 'DESC')->first();
                                SolicitudChangeStatus::dispatch($solicitud, $last_status, $emails_copy);
                                $total++;
                            }
                        }
                    }
                });


            $this->info("Comando ejecutado correctamente.");
            $this->info("$total solicitudes actualizadas.");
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
