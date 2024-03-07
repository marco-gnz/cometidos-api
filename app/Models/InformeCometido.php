<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

class InformeCometido extends Model
{
    protected $table        = "informe_cometidos";
    protected $primaryKey   = 'id';

    public const STATUS_INGRESO_EN_PLAZO    = 0;
    public const STATUS_INGRESO_TARDIO      = 1;

    public const STATUS_INGRESO_NOM = [
        self::STATUS_INGRESO_EN_PLAZO   => 'En plazo',
        self::STATUS_INGRESO_TARDIO     => 'Tardío'
    ];

    public const STATUS_INGRESO_TYPE = [
        self::STATUS_INGRESO_EN_PLAZO   => 'primary',
        self::STATUS_INGRESO_TARDIO     => 'danger'
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
            $dias_permitidos            = self::getDiasPermitidos($informe);
            $informe->codigo            = self::generarCodigo($informe);
            $informe->dias_permitidos   = $dias_permitidos;
            $informe->status_ingreso    = self::statusIngreso($informe);
            $informe->save();
        });
    }

    public function statusIngreso($informe)
    {
        $dias_permitidos        = self::getDiasPermitidos($informe);
        $fecha_termino_informe  = "{$informe->fecha_termino} {$informe->hora_termino}";
        $fecha_termino_informe  = Carbon::parse($fecha_termino_informe);
        $fecha_ingreso          = Carbon::parse($informe->fecha_by_user);
        $plazo                  = $fecha_termino_informe->addDays($dias_permitidos);

        if ($fecha_ingreso->lte($plazo)) {
            return self::STATUS_INGRESO_EN_PLAZO;
        } else {
            return self::STATUS_INGRESO_TARDIO;
        }
    }

    protected function getDiasPermitidos($informe)
    {
        return $informe->solicitud->dias_permitidos;
    }


    public function diffPlazoTardioInforme()
    {
        if ($this->status_ingreso === self::STATUS_INGRESO_TARDIO) {
            $dias_permitidos                = (int)$this->dias_permitidos;
            $fecha_termino_informe          = "{$this->fecha_termino} {$this->hora_termino}";
            $fecha_termino_informe          = Carbon::parse($fecha_termino_informe);
            $fecha_ingreso                  = Carbon::parse($this->fecha_by_user);
            $plazo                          = $fecha_termino_informe->addDays($dias_permitidos);
            $diferencia                     = $fecha_ingreso->diff($plazo);
            $dias                           = $diferencia->days;
            $horas                          = $diferencia->h;
            $minutos                        = $diferencia->i;
            return "El Informe se ingresó después del plazo de $dias_permitidos días. La diferencia es de $dias días, $horas horas y $minutos minutos.";
        } else {
            return null;
        }
    }

    private static function generarCodigo($informe)
    {
        $letra                  = "I";
        $correlativo            = $informe->id;
        $anio                   = $informe->created_at->year;
        $codigo                 = "{$anio}-{$correlativo}-{$letra}";
        return $codigo;
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
        return $this->hasMany(EstadoInformeCometido::class)->orderBy('id', 'DESC');
    }

    public function addEstados(array $estados)
    {
        return $this->estados()->createMany($estados);
    }

    public function authorizedToView()
    {
        return Gate::allows('view', $this);
    }

    public function firmaJefatura()
    {
        $firma = $this->estados()->where('status', EstadoInformeCometido::STATUS_APROBADO)->first();
        if($firma){
            $nombres    = $firma->userBy->abreNombres();
            $fecha      = Carbon::parse($firma->fecha_by_user)->format('d-m-y H:i:s');
            $new_firma  = "$nombres $fecha";
            return $new_firma;
        }
        return null;
    }

    public function firmaFuncionario()
    {
        $firma = $this->estados()->where('status', EstadoInformeCometido::STATUS_INGRESADA)->first();
        if($firma){
            $nombres    = $firma->userBy->abreNombres();
            $fecha      = Carbon::parse($firma->fecha_by_user)->format('d-m-y H:i:s');
            $new_firma  = "$nombres $fecha";
            return $new_firma;
        }
        return null;
    }
}
