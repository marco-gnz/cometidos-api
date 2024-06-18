<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    protected $table        = "departamentos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'cod_sirh',
        'nombre'
    ];

    public function grupos()
    {
        return $this->hasMany(Grupo::class);
    }

    public function contratos()
    {
        return $this->hasMany(Contrato::class);
    }
}
