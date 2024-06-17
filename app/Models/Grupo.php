<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class Grupo extends Model
{
    use HasFactory;

    protected $table        = "grupos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'establecimiento_id',
        'departamento_id',
        'sub_departamento_id',
        'user_id_by',
        'fecha_by_user',
        'user_id_update',
        'fecha_by_user_update',
    ];

    protected static function booted()
    {
        static::creating(function ($grupo) {
            $grupo->uuid                    = Str::uuid();
            $grupo->user_id_by             = Auth::check() ? Auth::user()->id : null;
            $grupo->fecha_by_user           = now();
        });

        static::updating(function ($grupo) {
            $grupo->user_id_update              = Auth::user()->id;
            $grupo->fecha_by_user_update        = now();
        });
    }

    public function firmantes()
    {
        return $this->hasMany(Firmante::class)->orderBy('posicion_firma', 'ASC');
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class);
    }

    public function addFirmantes(array $firmantes)
    {
        return $this->firmantes()->createMany($firmantes);
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

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id_by');
    }

    public function authorizedToDelete()
    {
        return Gate::allows('delete', $this);
    }

    public function authorizedToUpdate()
    {
        return Gate::allows('update', $this);
    }

    public function scopeSearchEstablecimiento($query, $params)
    {
        if ($params)
            return $query->whereHas('establecimiento', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeSearchDepto($query, $params)
    {
        if ($params)
            return $query->whereHas('departamento', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeSearchSubdepto($query, $params)
    {
        if ($params)
            return $query->whereHas('subdepartamento', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeSearchPerfil($query, $params)
    {
        if ($params)
            return $query->whereHas('firmantes.perfil', function ($q) use ($params) {
                $q->whereIn('id', $params);
            });
    }

    public function scopeSearchInput($query, $params)
    {
        if ($params)
            return $query->where('id', 'like', '%' . $params . '%')
                ->orWhere(function ($query) use ($params) {
                    $query->whereHas('departamento', function ($query) use ($params) {
                        $query->where('nombre', 'like', '%' . $params . '%');
                    });
                })
            ->orWhere(function ($query) use ($params) {
                $query->whereHas('subdepartamento', function ($query) use ($params) {
                    $query->where('nombre', 'like', '%' . $params . '%');
                });
            })
                ->orWhere(function ($query) use ($params) {
                    $query->whereHas('firmantes.funcionario', function ($query) use ($params) {
                        $query->where('rut_completo', 'like', '%' . $params . '%')
                            ->orWhere('rut', 'like', '%' . $params . '%')
                            ->orWhere('nombres', 'like', '%' . $params . '%')
                            ->orWhere('apellidos', 'like', '%' . $params . '%')
                            ->orWhere('nombre_completo', 'like', '%' . $params . '%')
                            ->orWhere('email', 'like', '%' . $params . '%');
                    });
                });
    }
}
