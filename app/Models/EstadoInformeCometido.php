<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class EstadoInformeCometido extends Model
{
    protected $table        = "estado_informe_cometidos";
    protected $primaryKey   = 'id';

    public const STATUS_INGRESADA  = 0;
    public const STATUS_APROBADO   = 1;
    public const STATUS_RECHAZADO  = 2;
    public const STATUS_MODIFICADO = 3;

    public const STATUS_NOM = [
        self::STATUS_INGRESADA  => 'INGRESADO',
        self::STATUS_APROBADO   => 'APROBADO',
        self::STATUS_RECHAZADO  => 'RECHAZADO',
        self::STATUS_MODIFICADO  => 'MODIFICADO'
    ];

    public const STATUS_TYPE = [
        self::STATUS_INGRESADA  => 'info',
        self::STATUS_APROBADO   => 'success',
        self::STATUS_RECHAZADO  => 'danger',
        self::STATUS_MODIFICADO => 'info'
    ];

    public const STATUS_INFORME = [
        ['id' => self::STATUS_INGRESADA, 'nombre' => self::STATUS_NOM[self::STATUS_INGRESADA]],
        ['id' => self::STATUS_MODIFICADO, 'nombre' => self::STATUS_NOM[self::STATUS_MODIFICADO]],
        ['id' => self::STATUS_APROBADO, 'nombre' => self::STATUS_NOM[self::STATUS_APROBADO]],
        ['id' => self::STATUS_RECHAZADO, 'nombre' => self::STATUS_NOM[self::STATUS_RECHAZADO]]

    ];

    public $timestamps = false;

    protected $fillable = [
        'status',
        'uuid',
        'status',
        'posicion_firma',
        'is_subrogante',
        'observacion',
        'ip_address',
        'role_id',
        'informe_cometido_id',
        'user_id_by',
        'fecha_by_user'
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
            $estado->informeCometido->update([
                'last_status'               => $estado->status
            ]);
        });
    }

    public function informeCometido()
    {
        return $this->belongsTo(InformeCometido::class, 'informe_cometido_id');
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
