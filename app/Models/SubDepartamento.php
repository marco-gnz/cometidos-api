<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubDepartamento extends Model
{
    protected $table        = "sub_departamentos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'cod_sirh',
        'nombre'
    ];
}
