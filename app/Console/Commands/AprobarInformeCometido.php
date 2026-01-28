<?php

namespace App\Console\Commands;

use App\Events\InformeCometidoStatus;
use App\Models\Configuration;
use App\Models\EstadoInformeCometido;
use App\Models\EstadoSolicitud;
use App\Models\InformeCometido;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Services\FeriadosService;

class AprobarInformeCometido extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:informe-aprobar-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aprobar informe de cometido ya procesados';

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
        $status_informe_ok      = [EstadoInformeCometido::STATUS_INGRESADA, EstadoInformeCometido::STATUS_MODIFICADO];
        $informes               = InformeCometido::whereIn('last_status', $status_informe_ok)
            ->whereHas('solicitud', function ($q) {
                $q->where('is_reasignada', false)
                    ->whereIn('status', [Solicitud::STATUS_PROCESADO, Solicitud::STATUS_EN_PROCESO])
                    ->whereHas('estados', function ($q) {
                        $q->where('status', EstadoSolicitud::STATUS_APROBADO)
                            ->where('s_role_id', 3);
                    });
            })
            ->get();

        if (count($informes) > 0) {
            foreach ($informes as $informe) {
                $last_status_informe = $informe->estados()->orderBy('id', 'DESC')->first();

                $last_movimiento_informe_date       = Carbon::parse($last_status_informe->fecha_by_user);
                $dias_plazo_aprobacion_informe      = (int)Configuration::obtenerValor('solicitud.dias_plazo_aprobacion_informe', $informe->solicitud->establecimiento_id);
                $array_fechas_feriados              = $this->feriados($last_movimiento_informe_date);
                $fechaLimite                        = $this->calcularFechaLimite($last_movimiento_informe_date, $dias_plazo_aprobacion_informe, $array_fechas_feriados);

                $now = Carbon::now();
                if ($fechaLimite->lessThanOrEqualTo($now)) {
                    $last_status_jd = $informe->solicitud->estados()
                        ->where('status', EstadoSolicitud::STATUS_APROBADO)
                        ->where('s_role_id', 3)
                        ->first();

                    if ($last_status_jd) {
                        $status = EstadoInformeCometido::STATUS_APROBADO;

                        $role_id = $last_status_jd->s_role_id;
                        $user_id = $last_status_jd->user_id;
                        $estados[] = [
                            'status'                    => $status,
                            'informe_cometido_id'       => $informe->id,
                            'observacion'               => "GECOM: Aprobación automática vía sistema debido a inactividad de firma en ${dias_plazo_aprobacion_informe} días hábiles contados desde la fecha de ingreso/modificacion del informe.",
                            'is_subrogante'             => $last_status_jd->is_subrogante,
                            'role_id'                   => $role_id,
                            'posicion_firma'            => $last_status_jd->posicion_firma,
                            'user_id_by'                => $user_id,
                            'ip_address'                => NULL
                        ];

                        $create_status      = $informe->addEstados($estados);
                        $informe    = $informe->fresh();
                        if ($create_status) {
                            $estados = [];
                            $last_status = $informe->estados()->orderBy('id', 'DESC')->first();
                            InformeCometidoStatus::dispatch($last_status);
                        }
                    }
                }
            }
        }
    }

    private function feriados($fecha)
    {
        try {
            $feriadosService = app(FeriadosService::class);
            $feriados = $feriadosService->obtenerFeriados($fecha);
            return $feriados;
        } catch (\Exceptio $e) {
            Log::info("Error Service Feriados: {$exception->getMessage()}");
        }
    }

    private function calcularFechaLimite(Carbon $fechaInicio, $diasHabiles, array $feriados)
    {
        $fechaLimite = $fechaInicio->copy();

        $feriados = array_filter(array_map(function ($feriado) {
            $feriadoCarbon = Carbon::parse($feriado);
            return !$feriadoCarbon->isWeekend() ? $feriadoCarbon : null;
        }, $feriados));

        while ($diasHabiles > 0) {
            $fechaLimite->addDay();
            if (!$fechaLimite->isWeekend() && !in_array($fechaLimite->format('Y-m-d'), array_map(function ($feriado) {
                return $feriado->format('Y-m-d');
            }, $feriados))) {
                $diasHabiles--;
            }
        }

        return $fechaLimite;
    }
}
