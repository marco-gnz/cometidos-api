<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class Grupo extends Model
{
    use HasFactory;

    protected $table        = "grupos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'codigo',
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
            $grupo->uuid                   = Str::uuid();
            $grupo->user_id_by             = Auth::check() ? Auth::user()->id : null;
            $grupo->fecha_by_user          = now();
        });

        static::created(function ($grupo) {
            $grupo->codigo                 = self::generateCodigo($grupo);
            $grupo->save();

            $contratos = Contrato::where('establecimiento_id', $grupo->establecimiento_id)
                ->where('departamento_id', $grupo->departamento_id)
                ->where('sub_departamento_id', $grupo->sub_departamento_id)
                ->whereNull('grupo_id')
                ->get();

            if (count($contratos) > 0) {
                $contratos->toQuery()->update([
                    'grupo_id'    => $grupo->id
                ]);
            }
        });

        static::updating(function ($grupo) {
            $grupo->user_id_update              = Auth::check() ? Auth::user()->id : null;
            $grupo->fecha_by_user_update        = now();
        });

        static::deleting(function ($grupo) {
            $contratos = $grupo->contratos()->get();
            if (count($contratos) > 0) {
                $contratos->toQuery()->update([
                    'grupo_id'    => NULL
                ]);
            }
        });
    }

    protected static function generateCodigo($grupo)
    {
        $duplicados = Grupo::where('establecimiento_id', $grupo->establecimiento_id)
            ->where('departamento_id', $grupo->departamento_id)
            ->where('sub_departamento_id', $grupo->sub_departamento_id)
            ->where('id', '!=', $grupo->id)
            ->orderByRaw('CAST(codigo AS UNSIGNED) ASC')
            ->get();

        $n_duplicados = count($duplicados);
        if ($n_duplicados > 0) {
            $first_codigo_duplicado = $duplicados[0]['codigo'];
            $codigo_duplicado       = preg_replace('/_\d+$/', '', $first_codigo_duplicado);
            $codigo                 = $codigo_duplicado . '_' . $n_duplicados;
        } else {
            $total  = Grupo::whereRaw('codigo NOT REGEXP "_[0-9]+$"')->count();
            $codigo = $total + 1;
        }
        return $codigo;
    }

    public function jefaturaDirecta()
    {
        $j_d = $this->firmantes()->where('role_id', 3)->where('status', true)->first();
        if ($j_d) {
            return $j_d->funcionario->abreNombres();
        }
        return null;
    }

    public function contratos()
    {
        return $this->hasMany(Contrato::class)->orderBy('id', 'ASC');
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
                ->orWhere('codigo', 'like', '%' . $params . '%')
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
