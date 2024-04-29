<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;
use Spatie\Permission\Models\Role;

class EstadoProcesoRendicionGasto extends Model
{
    use HasFactory;

    protected $table        = "estado_proceso_rendicion_gastos";
    protected $primaryKey   = 'id';

    public const STATUS_INGRESADA   = 0;
    public const STATUS_MODIFICADA  = 1;
    public const STATUS_APROBADO_JD = 2;
    public const STATUS_EN_PROCESO  = 3;
    public const STATUS_VERIFICADO  = 4;
    public const STATUS_APROBADO_N  = 5;
    public const STATUS_APROBADO_S  = 6;
    public const STATUS_ANULADO     = 7;

    public const STATUS_NOM = [
        self::STATUS_INGRESADA      => 'INGRESADO',
        self::STATUS_MODIFICADA     => 'MODIFICADO',
        self::STATUS_APROBADO_JD    => 'APROBADO JD',
        self::STATUS_EN_PROCESO     => 'EN PROCESO',
        self::STATUS_VERIFICADO     => 'VERIFICADO',
        self::STATUS_APROBADO_N     => 'APROBADO / SM',
        self::STATUS_APROBADO_S     => 'APROBADO / CM',
        self::STATUS_ANULADO        => 'ANULADO',
    ];

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'status',
        'observacion',
        'posicion_firma',
        'is_subrogante',
        'p_rendicion_gasto_id',
        'ip_address',
        'role_id',
        'user_id_by',
        'fecha_by_user',
        'user_id_update',
        'fecha_by_user_update',
    ];

    protected static function booted()
    {
        static::creating(function ($estado) {
            $ip_address                      = Request::ip();
            $estado->ip_address              = $ip_address;
            $estado->uuid                    = Str::uuid();
            $estado->user_id_by              = Auth::user()->id;
            $estado->fecha_by_user           = now();
        });

        static::created(function ($estado) {
            $estado->procesoRendicionGasto->update([
                'status'    => $estado->status
            ]);
        });
    }

    public function procesoRendicionGasto()
    {
        return $this->belongsTo(ProcesoRendicionGasto::class, 'p_rendicion_gasto_id');
    }

    public function perfil()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id_by');
    }
}
