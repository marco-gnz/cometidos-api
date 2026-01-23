<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InformeCometido extends Model
{
    protected $table        = "informe_cometidos";
    protected $primaryKey   = 'id';

    public const STATUS_INGRESO_EN_PLAZO    = 0;
    public const STATUS_INGRESO_TARDIO      = 1;

    public const STATUS_INGRESO_NOM = [
        self::STATUS_INGRESO_EN_PLAZO   => 'En plazo',
        self::STATUS_INGRESO_TARDIO     => 'Fuera de plazo'
    ];

    public const STATUS_INGRESO_TYPE = [
        self::STATUS_INGRESO_EN_PLAZO   => 'primary',
        self::STATUS_INGRESO_TARDIO     => 'danger'
    ];

    public const STATUS_INGRESO_INFORME = [
        ['id' => self::STATUS_INGRESO_EN_PLAZO, 'nombre' => self::STATUS_INGRESO_NOM[self::STATUS_INGRESO_EN_PLAZO]],
        ['id' => self::STATUS_INGRESO_TARDIO, 'nombre' => self::STATUS_INGRESO_NOM[self::STATUS_INGRESO_TARDIO]]
    ];

    protected $fillable = [
        'uuid',
        'codigo',
        'fecha_inicio',
        'fecha_termino',
        'dias_permitidos',
        'hora_llegada',
        'hora_salida',
        'utiliza_transporte',
        'actividad_realizada',
        'status_ingreso',
        'last_status',
        'solicitud_id',
        'user_id_by',
        'fecha_by_user',
    ];

    protected static function booted()
    {
        static::creating(function ($informe) {
            $informe->uuid          = Str::uuid();
            $informe->user_id_by    = Auth::user()->id;
            $informe->fecha_by_user = now();
        });

        static::created(function ($informe) {
            $dias_permitidos = (int) $informe->solicitud->dias_permitidos;

            $informe->updateQuietly([
                'codigo'          => self::generarCodigo($informe),
                'dias_permitidos' => $dias_permitidos,
                'status_ingreso'  => self::diffPlazoTardioInforme($informe),
            ]);
        });
    }

    private static function feriados($fecha)
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

    public static function diffPlazoTardioInforme($informe): int
    {
        $fecha_termino_cometido = Carbon::parse(
            "{$informe->solicitud->fecha_termino} {$informe->solicitud->hora_salida}"
        );

        $dias_permitidos       = (int) $informe->solicitud->dias_permitidos;
        $array_fechas_feriados = self::feriados($fecha_termino_cometido);

        $fechaLimite = self::calcularFechaLimite(
            $fecha_termino_cometido,
            $dias_permitidos,
            $array_fechas_feriados
        );

        $fecha_store_informe = Carbon::parse($informe->fecha_by_user);

        return $fecha_store_informe->lessThanOrEqualTo($fechaLimite)
            ? self::STATUS_INGRESO_EN_PLAZO
            : self::STATUS_INGRESO_TARDIO;
    }


    private static function calcularFechaLimite(Carbon $fechaInicio, $diasHabiles, array $feriados)
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

    protected static function getDiasPermitidos($informe): int
    {
        return (int) $informe->solicitud->dias_permitidos;
    }


    private static function generarCodigo($informe)
    {
        $anio = $informe->created_at->year;

        $maxCorrelativo = self::whereYear('created_at', $anio)
            ->whereNotNull('codigo')
            ->max(DB::raw("CAST(SUBSTRING_INDEX(codigo, '/', 1) AS UNSIGNED)"));

        $correlativo = $maxCorrelativo ? $maxCorrelativo + 1 : 1;

        return "{$correlativo}/{$anio}";
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function transportes()
    {
        return $this->belongsToMany(Transporte::class);
    }

    public function estados()
    {
        return $this->hasMany(EstadoInformeCometido::class);
    }

    public function addEstados(array $estados)
    {
        return $this->estados()->createMany($estados);
    }

    public function authorizedToView()
    {
        return Gate::allows('view', $this);
    }

    public function authorizedToAprobar()
    {
        return Gate::allows('aprobar', $this);
    }

    public function authorizedToRechazar()
    {
        return Gate::allows('rechazar', $this);
    }

    public function authorizedToUpdate()
    {
        return Gate::allows('update', $this);
    }

    public function authorizedToDelete()
    {
        return Gate::allows('delete', $this);
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id_by');
    }

    public function firmaJefatura()
    {
        $firma = $this->estados()->where('status', EstadoInformeCometido::STATUS_APROBADO)->where('role_id', 3)->first();
        if ($firma) {
            $nombres        = $firma->userBy->abreNombres();
            $fecha          = Carbon::parse($firma->fecha_by_user)->format('d-m-y H:i:s');
            $is_subrogante  = $firma->is_subrogante ? "(S)" : "";
            $new_firma      = "$nombres $fecha $is_subrogante";
            return $new_firma;
        }
        return null;
    }

    public function firmaFuncionario()
    {
        $firma = $this->estados()->where('status', EstadoInformeCometido::STATUS_INGRESADA)->first();
        if ($firma) {
            $nombres    = $firma->userBy->abreNombres();
            $fecha      = Carbon::parse($firma->fecha_by_user)->format('d-m-y H:i:s');
            $new_firma  = "$nombres $fecha";
            return $new_firma;
        }
        return null;
    }
}
