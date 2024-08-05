<?php

namespace App\Console\Commands;

use App\Models\Configuration;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Events\SolicitudChangeStatus;

class AnularSolicitud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:solicitud-anular';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Anular solicitudes por no tener actividad';

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
        $solicitudes = Solicitud::where('status', Solicitud::STATUS_EN_PROCESO)
            ->whereIn('last_status', [EstadoSolicitud::STATUS_RECHAZADO, EstadoSolicitud::STATUS_PENDIENTE])
            ->where('is_reasignada', true)
            ->get();

        if (count($solicitudes) > 0) {
            foreach ($solicitudes as $solicitud) {
                $firma_disponible   = $solicitud->firmantes()->where('posicion_firma', 0)->where('user_id', $solicitud->user_id)->first();
                $last_status_solicitud = $solicitud->estados()->orderBy('id', 'DESC')->first();

                $last_movimiento_solicitud_date     = Carbon::parse($last_status_solicitud->created_at);
                $dias_atraso_actividad              = (int)Configuration::obtenerValor('solicitud.dias_atraso_actividad', $solicitud->establecimiento_id);
                $array_fechas_feriados              = $this->feriados($last_movimiento_solicitud_date);
                $fechaLimite                        = $this->calcularFechaLimite($last_movimiento_solicitud_date, $dias_atraso_actividad, $array_fechas_feriados);
                $now = Carbon::now();
                if ($fechaLimite->lessThanOrEqualTo($now)) {
                    $status         = EstadoSolicitud::STATUS_ANULADO;
                    $observacion    = "GECOM: Anulación automática vía sistema debido a inactividad del usuario en ${dias_atraso_actividad} días hábiles contados desde la fecha de reasignación.";
                    $estados[] = [
                        'status'                    => $status,
                        'posicion_firma_s'          => $firma_disponible ? $firma_disponible->posicion_firma : null,
                        'solicitud_id'              => $solicitud->id,
                        'posicion_firma'            => $firma_disponible ? $firma_disponible->posicion_firma : null,
                        's_firmante_id'             => $firma_disponible ? $firma_disponible->id : null,
                        'observacion'               => $observacion,
                        'user_id'                   => $firma_disponible->user_id,
                        'movimiento_system'         => true
                    ];
                    $create_status   = $solicitud->addEstados($estados);
                    if ($create_status) {
                        $solicitud = $solicitud->fresh();
                        $emails_copy = [];
                        $ids_roles_anulado  = [2, 3];
                        if ($solicitud->derecho_pago) {
                            $ids_roles_anulado  = [2, 3, 6, 7];
                            $emails_copy        = $solicitud->firmantes()->whereIn('role_id', $ids_roles_anulado)->with('funcionario')->get()->pluck('funcionario.email')->toArray();
                        }
                        $last_status = $solicitud->estados()->orderBy('id', 'DESC')->first();
                        SolicitudChangeStatus::dispatch($solicitud, $last_status, $emails_copy);
                        $estados = [];
                    }
                }
            }
        }
    }

    private function feriados($fecha)
    {
        $fecha      = Carbon::parse($fecha);
        $anio       = $fecha->format('Y');
        $cacheKey   = "feriados_{$anio}";
        $feriados   = Cache::get($cacheKey);
        if ($feriados !== null) {
            return $feriados;
        }

        try {
            $url        = "https://apis.digital.gob.cl/fl/feriados/{$anio}";
            $response   = Http::get($url);
            if ($response->successful()) {
                $apiResponse = $response->body();
                $feriados = json_decode($apiResponse, true, 512, JSON_UNESCAPED_UNICODE);

                if (is_array($feriados)) {
                    $fechas = collect($feriados)->pluck('fecha')->toArray();
                    Cache::put($cacheKey, $fechas, now()->addDays(31));
                    return $fechas;
                }
            }
            return [];
        } catch (\Exception $exception) {
            Log::error("Error al procesar la solicitud de feriados: {$exception->getMessage()}");
            $feriados = Cache::get($cacheKey);
            return $feriados !== null ? $feriados : [];
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
