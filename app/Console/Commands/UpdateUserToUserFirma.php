<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Grupo;
use App\Models\Solicitud;

class UpdateUserToUserFirma extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:update-user-to-user-firma
                            {user_origen : ID del usuario que será reemplazado}
                            {user_reemplazo : ID del nuevo usuario}
                            {--role=5 : ID del rol del firmante}
                            {--execute : Ejecutar realmente los cambios}';

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

    private $userOrigen;
    private $userReemplazo;
    private $roleId;
    private $execute = false;

    private $resultados = [
        'grupos' => 0,
        'solicitudes_pendientes' => 0,
        'solicitudes_no_pendientes' => 0,
    ];

    public function handle()
    {
        try {
            $this->setParametros();

            if (!$this->validarParametros()) {
                return 1;
            }

            $this->mostrarConfiguracion();

            /*
             * Primero obtenemos TODO lo que sería modificado.
             * No realizamos ninguna actualización todavía.
             */
            $firmantesGrupos = $this->obtenerFirmantesGrupos();

            $firmantesSolicitudesPendientes =
                $this->obtenerFirmantesSolicitudesPendientes();

            $firmantesSolicitudesNoPendientes =
                $this->obtenerFirmantesSolicitudesNoPendientes();

            /*
             * Consolidamos las firmas de solicitudes y eliminamos
             * posibles duplicados por ID.
             */
            $firmantesSolicitudes = $firmantesSolicitudesPendientes
                ->concat($firmantesSolicitudesNoPendientes)
                ->unique('id')
                ->values();

            /*
             * Si las tablas/modelos de firmantes de grupo y solicitud
             * son distintos, se mantienen separados.
             */
            $this->mostrarResultados(
                $firmantesGrupos,
                $firmantesSolicitudesPendientes,
                $firmantesSolicitudesNoPendientes,
                $firmantesSolicitudes
            );

            if (!$this->execute) {
                $this->newLine();
                $this->warn('SIMULACIÓN finalizada. No se realizó ningún cambio.');

                return 0;
            }

            DB::transaction(function () use (
                $firmantesGrupos,
                $firmantesSolicitudes
            ) {
                $this->actualizarFirmantes($firmantesGrupos);
                $this->actualizarFirmantes($firmantesSolicitudes);
            });

            $this->newLine();
            $this->info('Actualización realizada correctamente.');

            return 0;
        } catch (\Throwable $e) {

            Log::error(
                'Error en comando update:update-user-to-user-firma',
                [
                    'user_origen' => $this->userOrigen,
                    'user_reemplazo' => $this->userReemplazo,
                    'role_id' => $this->roleId,
                    'execute' => $this->execute,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            $this->error('Error: ' . $e->getMessage());

            return 1;
        }
    }

    private function setParametros()
    {
        $this->userOrigen = (int) $this->argument('user_origen');
        $this->userReemplazo = (int) $this->argument('user_reemplazo');
        $this->roleId = (int) $this->option('role');
        $this->execute = (bool) $this->option('execute');
    }

    private function validarParametros()
    {
        if ($this->userOrigen <= 0) {
            $this->error('El usuario origen no es válido.');

            return false;
        }

        if ($this->userReemplazo <= 0) {
            $this->error('El usuario reemplazo no es válido.');

            return false;
        }

        if ($this->userOrigen === $this->userReemplazo) {
            $this->error(
                'El usuario origen y el usuario reemplazo no pueden ser el mismo.'
            );

            return false;
        }

        if ($this->roleId <= 0) {
            $this->error('El role_id no es válido.');

            return false;
        }

        return true;
    }

    private function mostrarConfiguracion()
    {
        $this->newLine();

        if ($this->execute) {
            $this->warn('MODO EJECUCIÓN - Se modificarán registros');
        } else {
            $this->info('MODO SIMULACIÓN - No se modificará ningún registro');
        }

        $this->newLine();

        $this->table(
            ['Parámetro', 'Valor'],
            [
                ['Usuario origen', $this->userOrigen],
                ['Usuario reemplazo', $this->userReemplazo],
                ['Role ID', $this->roleId],
                ['Ejecutar cambios', $this->execute ? 'SÍ' : 'NO'],
            ]
        );
    }

    /**
     * Firmantes pertenecientes a grupos.
     */
    private function obtenerFirmantesGrupos(): Collection
    {
        $firmantes = collect();

        $grupos = Grupo::whereHas('firmantes', function ($query) {
            $query->where('user_id', $this->userOrigen)
                ->where('role_id', $this->roleId);
        })->get();

        foreach ($grupos as $grupo) {

            $grupoFirmantes = $grupo->firmantes()
                ->where('user_id', $this->userOrigen)
                ->where('role_id', $this->roleId)
                ->get();

            $firmantes = $firmantes->concat($grupoFirmantes);
        }

        return $firmantes
            ->unique('id')
            ->values();
    }

    /**
     * Firmas que actualmente están pendientes para el usuario origen.
     */
    private function obtenerFirmantesSolicitudesPendientes(): Collection
    {
        $firmantes = collect();

        $solicitudes = Solicitud::where('status', Solicitud::STATUS_EN_PROCESO)
        ->whereHas('firmantes', function ($query) {
            $query->whereRaw('solicituds.posicion_firma_ok = solicitud_firmantes.posicion_firma')
                ->where('solicituds.is_reasignada', 0)
                ->where('status', true)
                ->where('is_executed', false)
                ->where('role_id', $this->roleId)
                ->where('user_id', $this->userOrigen);
        })
            ->get();

        foreach ($solicitudes as $solicitud) {

            $solicitudFirmantes = $solicitud
                ->firmantes('role_id', $this->roleId)
                ->where('user_id', $this->userOrigen)
                ->get();

            $firmantes = $firmantes->concat($solicitudFirmantes);
        }

        return $firmantes
            ->unique('id')
            ->values();
    }

    /**
     * Firmas aún no ejecutadas pertenecientes a solicitudes en proceso.
     */
    private function obtenerFirmantesSolicitudesNoPendientes(): Collection
    {
        $firmantes = collect();

        $solicitudes = Solicitud::whereHas('firmantes', function ($query) {
            $query->where('role_id', $this->roleId)
                ->where('user_id', $this->userOrigen)
                ->where('is_executed', false);
        })
            ->where('status', Solicitud::STATUS_EN_PROCESO)
            ->get();

        foreach ($solicitudes as $solicitud) {

            $solicitudFirmantes = $solicitud
                ->firmantes('role_id', $this->roleId)
                ->where('user_id', $this->userOrigen)
                ->where('is_executed', false)
                ->get();

            $firmantes = $firmantes->concat($solicitudFirmantes);
        }

        return $firmantes
            ->unique('id')
            ->values();
    }

    /**
     * Ejecuta efectivamente la actualización.
     */
    private function actualizarFirmantes(Collection $firmantes)
    {
        foreach ($firmantes as $firmante) {
            $firmante->update([
                'user_id' => $this->userReemplazo,
            ]);
        }
    }

    private function mostrarResultados(
        Collection $firmantesGrupos,
        Collection $firmantesPendientes,
        Collection $firmantesNoPendientes,
        Collection $firmantesSolicitudes
    ) {
        $duplicadosSolicitudes = $firmantesPendientes
            ->pluck('id')
            ->intersect($firmantesNoPendientes->pluck('id'))
            ->unique()
            ->count();

        $totalGrupos = $firmantesGrupos->count();
        $totalSolicitudes = $firmantesSolicitudes->count();
        $total = $totalGrupos + $totalSolicitudes;

        $this->newLine();

        $this->table(
            ['Tipo', 'Cantidad'],
            [
                [
                    'Firmantes en grupos',
                    $totalGrupos,
                ],
                [
                    'Firmas pendientes encontradas',
                    $firmantesPendientes->count(),
                ],
                [
                    'Firmas no pendientes encontradas',
                    $firmantesNoPendientes->count(),
                ],
                [
                    'Coincidencias entre ambas consultas',
                    $duplicadosSolicitudes,
                ],
                [
                    'Firmas únicas de solicitudes',
                    $totalSolicitudes,
                ],
                [
                    'TOTAL REAL A ACTUALIZAR',
                    $total,
                ],
            ]
        );

        /*
     * Mostrar máximo 3 solicitudes de ejemplo
     * para revisión manual.
     */
        $solicitudesEjemplo = Solicitud::whereIn(
            'id',
            $firmantesSolicitudes
                ->pluck('solicitud_id')
                ->filter()
                ->unique()
                ->shuffle()
                ->take(3)
        )
            ->get(['id', 'codigo']);

        if ($solicitudesEjemplo->isNotEmpty()) {

            $this->newLine();

            $this->info('Ejemplos de solicitudes afectadas para revisión manual:');

            $this->table(
                ['ID', 'Código'],
                $solicitudesEjemplo
                    ->map(function ($solicitud) {
                        return [
                            $solicitud->id,
                            $solicitud->codigo,
                        ];
                    })
                    ->toArray()
            );
        }

        $this->newLine();

        if (!$this->execute) {
            $this->warn(
                "{$total} registros únicos serían actualizados."
            );
        } else {
            $this->warn(
                "{$total} registros únicos serán actualizados."
            );
        }
    }
}
