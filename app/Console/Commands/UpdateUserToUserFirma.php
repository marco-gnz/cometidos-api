<?php

namespace App\Console\Commands;

use App\Models\Grupo;
use App\Models\Solicitud;
use Illuminate\Console\Command;

class UpdateUserToUserFirma extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:update-user-to-user-firma';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reemplazar usuario en grupos de firma y circuitos de firma.';

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
            $id_rail_asenjo         = 448;
            $id_alvarado_naguil     = 3992;

            $this->info('Iniciando actualización de grupos de firma...');

            $actualizados_grupos_2 = 0;
            $nuevos_grupos_3 = 0;
            $firmantes_creados = 0;
            $contratos_actualizados = 0;
            $firmas_pendiente_solicitud_actualizadas = 0;
            $firmas_no_pendiente_solicitud_actualizadas = 0;

            $grupos_1_count = Grupo::whereHas('firmantes', fn($q) => $q->where('user_id', $id_rail_asenjo)->where('role_id', 6))
                ->count();
            $this->info("Grupos con Rail Asenjo como Rev. Finanzas: $grupos_1_count");

            $grupos_2 = Grupo::whereHas('firmantes', fn($q) => $q->where('user_id', $id_rail_asenjo)->where('role_id', 6))
                ->get();

            foreach ($grupos_2 as $grupo) {
                $rev_finanzas = $grupo->firmantes()->where('user_id', $id_rail_asenjo)->where('role_id', 6)->first();
                if ($rev_finanzas) {
                    $rev_finanzas->update(['user_id' => $id_alvarado_naguil]);
                    $actualizados_grupos_2++;
                }
            }

            $solicitudes = Solicitud::firmantesPendiente([$id_rail_asenjo])
                ->get();

            foreach ($solicitudes as $solicitud) {
                $firma_rev_finanzas = $solicitud->firmantes('role_id', 6)
                    ->where('user_id', $id_rail_asenjo)
                    ->first();

                if ($firma_rev_finanzas) {
                    $firma_rev_finanzas->update(['user_id' => $id_alvarado_naguil]);
                    $firmas_pendiente_solicitud_actualizadas++;
                }
            }

            $solicitudes = Solicitud::whereHas('firmantes', function ($q) use ($id_rail_asenjo) {
                $q->where('role_id', 6)
                    ->where('user_id', $id_rail_asenjo)
                    ->where('is_executed', false);
            })
                ->where('status', Solicitud::STATUS_EN_PROCESO)
                ->get();

            foreach ($solicitudes as $solicitud) {
                $firma_rev_finanzas = $solicitud->firmantes('role_id', 6)
                    ->where('user_id', $id_rail_asenjo)
                    ->first();

                if ($firma_rev_finanzas) {
                    $firma_rev_finanzas->update(['user_id' => $id_alvarado_naguil]);
                    $firmas_no_pendiente_solicitud_actualizadas++;
                }
            }

            $this->info("✅ Grupos modificados en que se reemplaza a Rail Asenjo por Alvarado Naguil: $actualizados_grupos_2");
            $this->info("✅ Firmas de solicitudes actualizadas pendientes por Rail Asenjo y se reemplaza por Alvarado Naguil: $firmas_pendiente_solicitud_actualizadas");
            $this->info("✅ Firmas de solicitudes actualizadas NO pendientes por Rail Asenjo y se reemplaza por Alvarado Naguil:: $firmas_no_pendiente_solicitud_actualizadas");
        } catch (\Exception $e) {
            Log::error('Error en comando update:update-user-to-user-firma: ' . $e->getMessage());
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}
