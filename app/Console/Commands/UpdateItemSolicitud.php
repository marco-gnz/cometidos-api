<?php

namespace App\Console\Commands;

use App\Models\ItemPresupuestarioUser;
use App\Models\Solicitud;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateItemSolicitud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:update-item-solicitud';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
            $totalRevisados     = 0;
            $totalActualizados  = 0;
            $malos              = [];

            $map = ItemPresupuestarioUser::query()
                ->select(['calidad_id', 'ley_id', 'item_presupuestario_id'])
                ->get()
                ->mapWithKeys(function ($r) {
                    $leyKey = $r->ley_id ?? 0;
                    return ["{$r->calidad_id}|{$leyKey}" => (int)$r->item_presupuestario_id];
                });

            Solicitud::query()
                ->select(['id', 'codigo', 'calidad_id', 'ley_id', 'item_presupuestario_id'])
                ->whereNotNull('calidad_id')
                ->chunkById(500, function ($solicitudes) use (&$totalRevisados, &$totalActualizados, &$malos, $map) {

                    foreach ($solicitudes as $s) {
                        $totalRevisados++;

                        $leyKey = ($s->calidad_id == 2) ? 0 : ($s->ley_id ?? 0);
                        $key    = "{$s->calidad_id}|{$leyKey}";

                        $correct = $map[$key] ?? null;

                        if (!$correct) {
                            $malos[] = [
                                'codigo'      => $s->codigo,
                                'calidad_id'  => $s->calidad_id,
                                'ley_id'      => $s->ley_id,
                                'actual'      => $s->item_presupuestario_id,
                                'correcto'    => null,
                                'motivo'      => 'Sin mapeo en item_presupuestario_users',
                            ];
                            continue;
                        }

                        if ((int)$s->item_presupuestario_id !== (int)$correct) {
                            Solicitud::whereKey($s->id)->update([
                                'item_presupuestario_id' => $correct
                            ]);

                            $totalActualizados++;

                            $malos[] = [
                                'codigo'      => $s->codigo,
                                'calidad_id'  => $s->calidad_id,
                                'ley_id'      => $s->ley_id,
                                'actual'      => $s->item_presupuestario_id,
                                'correcto'    => $correct,
                                'motivo'      => 'Actualizado',
                            ];
                        }
                    }
                });

            $this->info("Revisados: {$totalRevisados}");
            $this->info("Actualizados: {$totalActualizados}");
            $this->info("Registros con observaciones: " . count($malos));
        } catch (\Throwable $e) {
            Log::error('Error repair item_presupuestario_id', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error($e->getMessage());
        }
    }
}
