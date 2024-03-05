<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    protected $table        = "cargos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'cod_sirh',
        'nombre'
    ];
}
