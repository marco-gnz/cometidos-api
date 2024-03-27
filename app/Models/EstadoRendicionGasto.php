<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;

class EstadoRendicionGasto extends Model
{
    protected $table        = "estado_rendicion_gastos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'status',
        'is_updated_mount',
        'observacion',
        'rendicion_gasto_id',
        'rendicion_old',
        'rendicion_new',
        'user_id_by',
        'fecha_by_user',
        'user_id_update',
        'fecha_by_user_update',
    ];

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

    protected static function booted()
    {
        static::creating(function ($estado) {
            $estado->uuid                    = Str::uuid();
            $estado->user_id_by              = Auth::user()->id;
            $estado->fecha_by_user           = now();
            $estado->ip_address              = Request::ip();
        });

        static::created(function ($estado) {
            $isUpdatedMount             = self::isUpdateMount($estado);
            $estado->is_updated_mount   = $isUpdatedMount;
            $estado->save();
        });
    }

    public function isUpdateMount($estado)
    {
        if ($estado->rendicionGasto->mount === $estado->rendicionGasto->mount_real) {
            return false;
        } else {
            return true;
        }
    }

    public function rendicionGasto()
    {
        return $this->belongsTo(RendicionGasto::class, 'rendicion_gasto_id');
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id_by');
    }
}
