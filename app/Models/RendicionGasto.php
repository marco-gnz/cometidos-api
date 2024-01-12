<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RendicionGasto extends Model
{
    protected $table        = "rendicion_gastos";
    protected $primaryKey   = 'id';

    public const STATUS_PENDIENTE = 0;
    public const STATUS_APROBADO  = 1;
    public const STATUS_RECHAZADO = 2;

    public const STATUS_NOM = [
        self::STATUS_PENDIENTE => 'PENDIENTE',
        self::STATUS_APROBADO  => 'APROBADO',
        self::STATUS_RECHAZADO => 'RECHAZADO',
    ];

    public const STATUS_DESC = [
        self::STATUS_PENDIENTE => 'Pendiente por validar',
        self::STATUS_APROBADO  => 'Aprobado por administrador',
        self::STATUS_RECHAZADO => 'Rechazado por administrador',
    ];

    protected $fillable = [
        'uuid',
        'mount',
        'mount_real',
        'last_status',
        'rinde_gasto',
        'rinde_gastos_servicio',
        'proceso_rendicion_gasto_id',
        'actividad_gasto_id',
        'user_id_by',
        'fecha_by_user',
        'user_id_update',
        'fecha_by_user_update',
    ];

    protected static function booted()
    {
        static::creating(function ($rendicion) {
            $rendicion->mount_real              = $rendicion->mount;
            $rendicion->uuid                    = Str::uuid();
            $rendicion->user_id_by              = Auth::user()->id;
            $rendicion->fecha_by_user           = now();
        });

        static::created(function ($rendicion) {
            $rendicion->estados()->create();
        });
    }

    public function actividad()
    {
        return $this->belongsTo(ActividadGasto::class, 'actividad_gasto_id');
    }

    public function procesoRendicionGasto()
    {
        return $this->belongsTo(ProcesoRendicionGasto::class, 'proceso_rendicion_gasto_id');
    }

    public function estados()
    {
        return $this->hasMany(EstadoRendicionGasto::class);
    }

    public function addStatus(array $data)
    {
        $this->estados()->createMany($data);
    }
}
