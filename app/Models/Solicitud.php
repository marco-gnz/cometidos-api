<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Solicitud extends Model
{
    protected $table        = "solicituds";
    protected $primaryKey   = 'id';

    public const STATUS_INGRESADA = 0;
    public const STATUS_PENDIENTE = 1;
    public const STATUS_APROBADO  = 2;
    public const STATUS_RECHAZADO = 3;

    public const STATUS_NOM = [
        self::STATUS_INGRESADA => 'INGRESADA',
        self::STATUS_PENDIENTE => 'PENDIENTE',
        self::STATUS_APROBADO  => 'APROBADO',
        self::STATUS_RECHAZADO => 'RECHAZADO',
    ];

    public const STATUS_DESC = [
        self::STATUS_INGRESADA => 'Ingresada al sistema',
        self::STATUS_PENDIENTE => 'Pendiente por validar',
        self::STATUS_APROBADO  => 'Aprobado por administrador',
        self::STATUS_RECHAZADO => 'Rechazado por administrador',
    ];

    protected $fillable = [
        'uuid',
        'codigo',
        'fecha_inicio',
        'fecha_termino',
        'hora_llegada',
        'hora_salida',
        'derecho_pago',
        'actividad_realizada',
        'gastos_alimentacion',
        'gastos_alojamiento',
        'pernocta_lugar_residencia',
        'n_dias_40',
        'n_dias_100',
        'observacion_gastos',
        'status',
        'last_status',
        'total_dias_cometido',
        'total_horas_cometido',
        'valor_cometido_diario',
        'valor_cometido_parcial',
        'valor_pasaje',
        'valor_total',
        'user_id',
        'grupo_id',
        'departamento_id',
        'sub_departamento_id',
        'establecimiento_id',
        'escala_id',
        'user_id_by',
        'fecha_by_user',
        'user_id_update',
        'fecha_by_user_update',
    ];

    public function grupoDepto($solicitud)
    {
        $grupo = Grupo::where('establecimiento_id', $solicitud->funcionario->establecimiento_id)
            ->where('departamento_id', $solicitud->funcionario->departamento_id)
            ->where('sub_departamento_id', $solicitud->funcionario->sub_departamento_id)
            ->first();

        return $grupo;
    }

    protected static function booted()
    {
        static::creating(function ($solicitud) {
            $fecha_inicio                       = Carbon::parse($solicitud->fecha_inicio);
            $fecha_termino                      = Carbon::parse($solicitud->fecha_termino);
            $total_dias                         = $fecha_inicio->diffInDays($fecha_termino) + 1;
            $hora_llegada                       = Carbon::parse($solicitud->hora_llegada)->format('H:i:s');
            $hora_salida                        = Carbon::parse($solicitud->hora_salida)->format('H:i:s');

            $ini_date_time                      = $fecha_inicio->format('Y-m-d') . ' ' . $hora_llegada;
            $ter_date_time                      = $fecha_termino->format('Y-m-d') . ' ' . $hora_salida;
            $ini_date_time                      = Carbon::parse($ini_date_time);
            $ter_date_time                      = Carbon::parse($ter_date_time);
            $total_horas_cometido               = $ini_date_time->diffInHours($ter_date_time);

            $solicitud->uuid                    = Str::uuid();
            $solicitud->total_dias_cometido     = $total_dias;
            $solicitud->total_horas_cometido    = $total_horas_cometido;
            $solicitud->user_id                 = Auth::user()->id;
            $solicitud->user_id_by              = Auth::user()->id;
            $solicitud->fecha_by_user           = now();
        });



        static::created(function ($solicitud) {
            // Generar el código de identificación usando el ID del registro
            $grupo                          = self::grupoDepto($solicitud);
            $codigoIdentificacion           = $solicitud->id * 1000 + mt_rand(1, 999);
            $solicitud->codigo              = $codigoIdentificacion;
            $solicitud->departamento_id     = $grupo ? $grupo->departamento_id : null;
            $solicitud->sub_departamento_id = $grupo ? $grupo->sub_departamento_id : null;
            $solicitud->establecimiento_id  = $grupo ? $grupo->establecimiento_id : null;
            $solicitud->grupo_id            = $grupo ? $grupo->id : null;
            $solicitud->save();
        });
    }

    public function motivos()
    {
        return $this->belongsToMany(Motivo::class);
    }

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function subdepartamento()
    {
        return $this->belongsTo(SubDepartamento::class, 'sub_departamento_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function lugares()
    {
        return $this->belongsToMany(Lugar::class);
    }

    public function transportes()
    {
        return $this->belongsToMany(Transporte::class);
    }

    public function actividades()
    {
        return $this->belongsToMany(ActividadGasto::class)->withPivot('mount', 'status', 'status_admin', 'rinde_gastos_servicio');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class)->orderBy('id', 'DESC');
    }

    public function estados()
    {
        return $this->hasMany(EstadoSolicitud::class)->orderBy('id', 'DESC');
    }

    public function procesoRendicionGastos()
    {
        return $this->hasMany(ProcesoRendicionGasto::class)->orderBy('id', 'DESC');
    }

    public function addDocumentos(array $documentos)
    {
        return $this->documentos()->createMany($documentos);
    }
}
