<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class SolicitudFirmante extends Model
{
    protected $table        = "solicitud_firmantes";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'posicion_firma',
        'is_reasignado',
        'status',
        'is_executed',
        'is_success',
        'permissions_id',
        'solicitud_id',
        'grupo_id',
        'user_id',
        'role_id',
        'user_id_by',
        'fecha_by_user',
        'user_id_update',
        'fecha_by_user_update'
    ];

    protected $casts = [
        'permissions_id' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($firmante) {
            $firmante->uuid                   = Str::uuid();
            $firmante->user_id_by             = Auth::user()->id;
            $firmante->fecha_by_user          = now();
        });

        static::deleting(function ($firmante) {
            $firmante->estados()->delete();
        });
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function estados()
    {
        return $this->hasMany(EstadoSolicitud::class, 'firmante_id');
    }

    public function estadosFirma()
    {
        return $this->hasMany(EstadoSolicitud::class, 's_firmante_id');
    }

    public function estadosreasignados()
    {
        return $this->hasMany(EstadoSolicitud::class, 'r_s_firmante_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function perfil()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function authorizedToUpdate()
    {
        return Gate::allows('update', $this);
    }
}
