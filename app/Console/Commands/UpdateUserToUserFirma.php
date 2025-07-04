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
            $id_user_a_reemplazar        = 3966;
            $id_user_reemplazo           = 41;

            $this->info('Iniciando actualización de grupos de firma...');

            $actualizados_grupos_2 = 0;
            $nuevos_grupos_3 = 0;
            $firmantes_creados = 0;
            $contratos_actualizados = 0;
            $firmas_pendiente_solicitud_actualizadas = 0;
            $firmas_no_pendiente_solicitud_actualizadas = 0;

            $grupos_1_count = Grupo::whereHas('firmantes', fn($q) => $q->where('user_id', $id_user_a_reemplazar)->where('role_id', 5))
                ->count();
            $this->info("Grupos con Usuario Origen: $grupos_1_count");

            $grupos_2 = Grupo::whereHas('firmantes', fn($q) => $q->where('user_id', $id_user_a_reemplazar)->where('role_id', 5))
                ->get();

            foreach ($grupos_2 as $grupo) {
                $origen = $grupo->firmantes()->where('user_id', $id_user_a_reemplazar)->where('role_id', 5)->first();
                if ($origen) {
                    $origen->update(['user_id' => $id_user_reemplazo]);
                    $actualizados_grupos_2++;
                }
            }

            $solicitudes = Solicitud::firmantesPendiente([$id_user_a_reemplazar])
                ->get();

            foreach ($solicitudes as $solicitud) {
                $firma_origen = $solicitud->firmantes('role_id', 5)
                    ->where('user_id', $id_user_a_reemplazar)
                    ->first();

                if ($firma_origen) {
                    $firma_origen->update(['user_id' => $id_user_reemplazo]);
                    $firmas_pendiente_solicitud_actualizadas++;
                }
            }

            $solicitudes = Solicitud::whereHas('firmantes', function ($q) use ($id_user_a_reemplazar) {
                $q->where('role_id', 5)
                    ->where('user_id', $id_user_a_reemplazar)
                    ->where('is_executed', false);
            })
                ->where('status', Solicitud::STATUS_EN_PROCESO)
                ->get();

            foreach ($solicitudes as $solicitud) {
                $firma_origen = $solicitud->firmantes('role_id', 5)
                    ->where('user_id', $id_user_a_reemplazar)
                    ->first();

                if ($firma_origen) {
                    $firma_origen->update(['user_id' => $id_user_reemplazo]);
                    $firmas_no_pendiente_solicitud_actualizadas++;
                }
            }

            $this->info("✅ Grupos modificados en que se reemplaza a Usuario Origen por Usuario Reemplazo: $actualizados_grupos_2");
            $this->info("✅ Firmas de solicitudes actualizadas pendientes por Usuario Origen y se reemplaza por Usuario Reemplazo: $firmas_pendiente_solicitud_actualizadas");
            $this->info("✅ Firmas de solicitudes actualizadas NO pendientes por Usuario Origen y se reemplaza por Usuario Reemplazo:: $firmas_no_pendiente_solicitud_actualizadas");
        } catch (\Exception $e) {
            Log::error('Error en comando update:update-user-to-user-firma: ' . $e->getMessage());
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}
