<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Grupo extends Model
{
    use HasFactory;

    protected $table        = "grupos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'establecimiento_id',
        'departamento_id',
        'sub_departamento_id'
    ];

    protected static function booted()
    {
        static::creating(function ($grupo) {
            $grupo->uuid                    = Str::uuid();
        });
    }

    public function firmantes()
    {
        return $this->hasMany(Firmante::class)->orderBy('posicion_firma', 'ASC');
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
}
