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

    public const RESOLUCION_AFECTA      = 0;
    public const RESOLUCION_DECRETO     = 1;
    public const RESOLUCION_EXENTA      = 2;
    public const RESOLUCION_PROYECTO    = 3;
    public const RESOLUCION_REGISTRO    = 4;

    public const RESOLUCION_NOM = [
        self::RESOLUCION_AFECTA         => 'AFECTA',
        self::RESOLUCION_DECRETO        => 'DECRETO',
        self::RESOLUCION_EXENTA         => 'EXENTA',
        self::RESOLUCION_PROYECTO       => 'PROYECTO',
        self::RESOLUCION_REGISTRO       => 'REGISTRO'
    ];

    public const JORNADA_MANANA         = 0;
    public const JORNADA_TARDE          = 1;
    public const JORNADA_NOCHE          = 2;
    public const JORNADA_TODO_EL_DIA    = 3;

    public const JORNADA_NOM = [
        self::JORNADA_MANANA        => 'Mañana',
        self::JORNADA_TARDE         => 'Tarde',
        self::JORNADA_NOCHE         => 'Noche',
        self::JORNADA_TODO_EL_DIA   => 'Todo el día'
    ];

    public const JORNADA_COMETIDOS = [
        ['id' => self::JORNADA_MANANA, 'nombre' => self::JORNADA_NOM[self::JORNADA_MANANA]],
        ['id' => self::JORNADA_TARDE, 'nombre' => self::JORNADA_NOM[self::JORNADA_TARDE]],
        ['id' => self::JORNADA_NOCHE, 'nombre' => self::JORNADA_NOM[self::JORNADA_NOCHE]],
        ['id' => self::JORNADA_TODO_EL_DIA, 'nombre' => self::JORNADA_NOM[self::JORNADA_TODO_EL_DIA]],
    ];

    public const STATUS_INGRESADA = 0;
    public const STATUS_PENDIENTE = 1;
    public const STATUS_APROBADO  = 2;
    public const STATUS_RECHAZADO = 3;
    public const STATUS_ANULADO   = 4;
    public const STATUS_MODIFICADA = 5;

    public const STATUS_NOM = [
        self::STATUS_INGRESADA  => 'INGRESADA',
        self::STATUS_PENDIENTE  => 'PENDIENTE',
        self::STATUS_APROBADO   => 'APROBADO',
        self::STATUS_RECHAZADO  => 'RECHAZADO',
        self::STATUS_ANULADO    => 'ANULADO',
        self::STATUS_MODIFICADA => 'MODIFICADA'
    ];

    public const STATUS_DESC = [
        self::STATUS_INGRESADA  => 'Ingresada al sistema',
        self::STATUS_PENDIENTE  => 'Pendiente por validar',
        self::STATUS_APROBADO   => 'Aprobado por administrador',
        self::STATUS_RECHAZADO  => 'Rechazado por administrador',
        self::STATUS_ANULADO    => 'Anulado por administrador',
        self::STATUS_MODIFICADA => 'Modificada por usuario'
    ];

    public const RECHAZO_1 = 1;
    public const RECHAZO_2 = 2;
    public const RECHAZO_3 = 3;

    public const RECHAZO_NOM = [
        self::RECHAZO_1 => 'FALTA DE ANTECEDENTES',
        self::RECHAZO_2 => 'ANTECEDENTES DE PROPUESTA DE SOLICITUD INCORRECTOS',
        self::RECHAZO_3 => 'FALTAN DOCUMENTOS ADJUNTOS',
    ];

    public const RECHAZO_DESC = [
        self::RECHAZO_1 => 'FALTA DE ANTECEDENTES',
        self::RECHAZO_2 => 'ANTECEDENTES DE PROPUESTA DE SOLICITUD INCORRECTOS',
        self::RECHAZO_3 => 'FALTAN DOCUMENTOS ADJUNTOS',
    ];

    public const RECHAZO_STATUS = [
        ['id' => self::RECHAZO_1, 'nombre' => self::RECHAZO_NOM[self::RECHAZO_1], 'desc' => self::RECHAZO_DESC[self::RECHAZO_1]],
        ['id' => self::RECHAZO_2, 'nombre' => self::RECHAZO_NOM[self::RECHAZO_2], 'desc' => self::RECHAZO_DESC[self::RECHAZO_2]],
        ['id' => self::RECHAZO_3, 'nombre' => self::RECHAZO_NOM[self::RECHAZO_3], 'desc' => self::RECHAZO_DESC[self::RECHAZO_3]],
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
        'fecha_resolucion',
        'n_resolucion',
        'tipo_resolucion',
        'jornada',
        'dentro_pais',
        'n_cargo_user',
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
        'ley_id',
        'grado_id',
        'establecimiento_id',
        'calculo_aplicado',
        'tipo_comision_id',
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
            $grupo                          = self::grupoDepto($solicitud);
            $solicitud->codigo              = self::generarCodigo($solicitud);
            $solicitud->departamento_id     = $grupo ? $grupo->departamento_id : null;
            $solicitud->sub_departamento_id = $grupo ? $grupo->sub_departamento_id : null;
            $solicitud->establecimiento_id  = $grupo ? $grupo->establecimiento_id : null;
            $solicitud->grupo_id            = $grupo ? $grupo->id : null;
            $solicitud->ley_id              = $solicitud->funcionario ? $solicitud->funcionario->ley_id : null;
            $solicitud->grado_id            = $solicitud->funcionario ? $solicitud->funcionario->grado_id : null;
            $solicitud->tipo_resolucion     = self::RESOLUCION_EXENTA;
            $solicitud->save();
        });

        static::updating(function ($solicitud) {
            $solicitud->codigo = self::generarCodigoUpdate($solicitud);
        });
    }

    private static function generarCodigo($solicitud)
    {
        $letra                  = $solicitud->derecho_pago ? 'C' : 'S';
        $correlativo            = str_pad(self::whereYear('created_at', $solicitud->created_at->year)->count() + 1, 4, '0', STR_PAD_LEFT);
        $anio                   = $solicitud->created_at->year;
        $codigoEstablecimiento  = $solicitud->funcionario->establecimiento->cod_sirh;
        $codigo                 = "{$codigoEstablecimiento}-{$anio}-{$correlativo}-{$letra}";
        return $codigo;
    }

    private static function generarCodigoUpdate($solicitud)
    {
        $codigo_actual  = $solicitud->codigo;
        $letra          = $solicitud->derecho_pago ? 'C' : 'S';
        list($codigoEstablecimiento, $anio, $correlativo) = explode('-', $codigo_actual);
        $codigo         = "{$codigoEstablecimiento}-{$anio}-{$correlativo}-{$letra}";
        return $codigo;
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

    public function ley()
    {
        return $this->belongsTo(Ley::class, 'ley_id');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }

    public function tipoComision()
    {
        return $this->belongsTo(TipoComision::class, 'tipo_comision_id');
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

    public function paises()
    {
        return $this->belongsToMany(Country::class, 'country_solicitud');
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
        return $this->hasMany(ProcesoRendicionGasto::class);
    }

    public function calculos()
    {
        return $this->hasMany(SoliucitudCalculo::class)->latest();
    }

    public function firmantes()
    {
        return $this->hasMany(SolicitudFirmante::class);
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id_by');
    }

    public function addDocumentos(array $documentos)
    {
        return $this->documentos()->createMany($documentos);
    }

    public function addFirmantes(array $firmantes)
    {
        return $this->firmantes()->createMany($firmantes);
    }

    public function addEstados(array $estados)
    {
        return $this->estados()->createMany($estados);
    }
}
