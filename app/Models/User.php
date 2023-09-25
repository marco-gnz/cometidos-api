<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'rut',
        'dv',
        'rut_completo',
        'nombres',
        'apellidos',
        'nombre_completo',
        'estado',
        'email',
        'telefono',
        'password',
        'ley_id',
        'grado_id',
        'cargo_id',
        'departamento_id',
        'sub_departamento_id',
        'establecimiento_id',
        'hora_id',
        'calidad_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function ley()
    {
        return $this->belongsTo(Ley::class, 'ley_id');
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

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class);
    }
}
