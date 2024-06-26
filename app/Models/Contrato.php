<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Contrato extends Model
{
    use HasFactory;

    protected $table        = "contratos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'ley_id',
        'estamento_id',
        'grado_id',
        'cargo_id',
        'departamento_id',
        'sub_departamento_id',
        'establecimiento_id',
        'hora_id',
        'calidad_id',
        'grupo_id',
        'user_id',
        'usuario_add_id',
        'fecha_add',
        'usuario_update_id',
        'fecha_update'
    ];

    protected static function booted()
    {
        static::creating(function ($contrato) {
            $contrato->uuid                  = Str::uuid();
            $contrato->usuario_add_id        = Auth::check() ? Auth::user()->id : null;
            $contrato->fecha_add            = now();
        });
    }

    public function isPosibleGrupos()
    {
        $grupos = Grupo::whereHas('firmantes', function ($q) {
            $q->where('role_id', 2);
        })->whereHas('firmantes', function ($q) {
            $q->where('role_id', 7);
        })
            ->where('establecimiento_id', $this->establecimiento_id)
            ->where('departamento_id', $this->departamento_id)
            ->whereHas('firmantes')
            ->orderByRaw('CAST(codigo AS UNSIGNED) ASC')
            ->get();

        $grupos->map(function ($grupo) {
            if ($grupo) {
                $existe_en_su_mismo_grupo = $grupo->firmantes()->where('user_id', $this->user_id)->first();
                $grupo->{'es_su_grupo'} =  $existe_en_su_mismo_grupo ? true : false;
            }
            return $grupo;
        });
        return $grupos;
    }

    public function ley()
    {
        return $this->belongsTo(Ley::class, 'ley_id');
    }

    public function estamento()
    {
        return $this->belongsTo(Estamento::class, 'estamento_id');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }

    public function calidad()
    {
        return $this->belongsTo(Calidad::class, 'calidad_id');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function subDepartamento()
    {
        return $this->belongsTo(SubDepartamento::class, 'sub_departamento_id');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }

    public function hora()
    {
        return $this->belongsTo(Hora::class, 'hora_id');
    }

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }
}
