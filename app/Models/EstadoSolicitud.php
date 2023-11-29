<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class EstadoSolicitud extends Model
{
    protected $table        = "estado_solicituds";
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
        'status',
        'observacion',
        'posicion_firma',
        'history_solicitud',
        'reasignacion',
        'reasignado',
        'solicitud_id',
        'user_id',
        'role_id',
        'user_firmante_id',
        'role_firmante_id'
    ];

    protected static function booted()
    {
        static::creating(function ($estado) {
            $estado->solicitud->update([
                'last_status'   => $estado->status
            ]);
        });
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function perfil()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function firmante()
    {
        return $this->belongsTo(User::class, 'user_firmante_id');
    }

    public function perfilFirmante()
    {
        return $this->belongsTo(Role::class, 'role_firmante_id');
    }
}
