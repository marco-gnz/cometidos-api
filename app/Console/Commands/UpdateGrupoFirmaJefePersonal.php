<?php

namespace App\Console\Commands;

use App\Models\Contrato;
use App\Models\Grupo;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateGrupoFirmaJefePersonal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:jefe-personal-grupofirma-ley19';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar jefes de personal';

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
            $id_norma       = 533;
            $id_yocelyne    = 397;

            $this->info('Iniciando actualización de grupos de firma...');

            $actualizados_grupos_2 = 0;
            $nuevos_grupos_3 = 0;
            $firmantes_creados = 0;
            $contratos_actualizados = 0;
            $firmas_pendiente_solicitud_actualizadas = 0;
            $firmas_no_pendiente_solicitud_actualizadas = 0;

            // 1. Mostrar cantidad de grupos mixtos donde Norma aparece como jefe de personal
            $grupos_1_count = Grupo::whereHas('contratos', fn($q) => $q->where('ley_id', 4))
                ->whereHas('firmantes', fn($q) => $q->where('user_id', $id_norma)->where('role_id', 4))
                ->count();
            $this->info("Grupos (ley 19 y/o otras leyes) con Norma como jefe personal: $grupos_1_count");

            // 2. Actualizar grupos de solo ley 19 donde Norma aparece como jefe personal
            $grupos_2 = Grupo::whereHas('contratos', fn($q) => $q->where('ley_id', 4))
                ->whereDoesntHave('contratos', fn($q) => $q->where('ley_id', '!=', 4))
                ->whereHas('firmantes', fn($q) => $q->where('user_id', $id_norma)->where('role_id', 4))
                ->get();

            foreach ($grupos_2 as $grupo) {
                $jefe_directo = $grupo->firmantes()->where('user_id', $id_norma)->where('role_id', 4)->first();
                if ($jefe_directo) {
                    $jefe_directo->update(['user_id' => $id_yocelyne]);
                    $actualizados_grupos_2++;
                }
            }

            // 3. Grupos mixtos: clonar y reemplazar jefe personal
            $grupos_3 = Grupo::whereHas('contratos', fn($q) => $q->where('ley_id', 4))
                ->whereHas('contratos', fn($q) => $q->where('ley_id', '!=', 4))
                ->whereHas('firmantes', fn($q) => $q->where('user_id', $id_norma)->where('role_id', 4))
                ->get();

            foreach ($grupos_3 as $grupo) {
                $newGrupo = Grupo::create($grupo->only([
                    'establecimiento_id',
                    'departamento_id',
                    'sub_departamento_id'
                ]));

                if (!$newGrupo) continue;

                $nuevos_grupos_3++;

                $firmantes = $grupo->firmantes;
                foreach ($firmantes as $firmante) {
                    $newGrupo->firmantes()->create([
                        'posicion_firma' => $firmante->posicion_firma,
                        'status'         => true,
                        'user_id'        => ($firmante->role_id === 4) ? $id_yocelyne : $firmante->user_id,
                        'role_id'        => $firmante->role_id,
                    ]);
                    $firmantes_creados++;
                }

                $grupo->contratos()
                    ->where('ley_id', 4)
                    ->get()
                    ->each(function ($contrato) use ($newGrupo, &$contratos_actualizados) {
                        $contrato->update(['grupo_id' => $newGrupo->id]);
                        $contratos_actualizados++;
                    });
            }

            // 4. Firmas pendientes por Norma en solicitudes ley 19
            $solicitudes = Solicitud::firmantesPendiente([$id_norma])
                ->where('ley_id', 4)
                ->get();

            foreach ($solicitudes as $solicitud) {
                $firma_jefe_personal = $solicitud->firmantes('role_id', 4)
                    ->where('user_id', $id_norma)
                    ->first();

                if ($firma_jefe_personal) {
                    $firma_jefe_personal->update(['user_id' => $id_yocelyne]);
                    $firmas_pendiente_solicitud_actualizadas++;
                }
            }

            //5. Firmas no pendientes por Norma (que están antes de su firma) en solicitudes ley 19 y que están EN PROCESO
            $solicitudes = Solicitud::whereHas('firmantes', function ($q) use ($id_norma) {
                $q->where('role_id', 4)
                    ->where('user_id', $id_norma)
                    ->where('is_executed', false);
            })
                ->where('status', Solicitud::STATUS_EN_PROCESO)
                ->where('ley_id', 4)
                ->get();

            foreach ($solicitudes as $solicitud) {
                $firma_jefe_personal = $solicitud->firmantes('role_id', 4)
                    ->where('user_id', $id_norma)
                    ->first();

                if ($firma_jefe_personal) {
                    $firma_jefe_personal->update(['user_id' => $id_yocelyne]);
                    $firmas_no_pendiente_solicitud_actualizadas++;
                }
            }

            $this->info("✅ Grupos modificados (solo ley 19) en que se reemplaza a Jocelyne por Norma: $actualizados_grupos_2");
            $this->info("✅ Nuevos grupos creados para contratos ley 19 - Jocelyne JP: $nuevos_grupos_3");
            $this->info("✅ Firmantes creados en nuevos grupos: $firmantes_creados");
            $this->info("✅ Contratos actualizados a nuevo grupo: $contratos_actualizados");
            $this->info("✅ Firmas de solicitudes actualizadas pendientes por Norma y se reemplaza por Jocelyne: $firmas_pendiente_solicitud_actualizadas");
            $this->info("✅ Firmas de solicitudes actualizadas NO pendientes por Norma y se reemplaza por Jocelyne: $firmas_no_pendiente_solicitud_actualizadas");
        } catch (\Exception $e) {
            Log::error('Error en comando update:jefe-personal-grupofirma-ley19: ' . $e->getMessage());
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}
