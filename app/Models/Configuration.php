<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $table        = "configurations";
    protected $primaryKey   = 'id';

    protected $fillable = ['clave', 'valor', 'tipo'];

    public static function obtenerValor($clave)
    {
        $configuracion = self::where('clave', $clave)->first();
        return $configuracion ? $configuracion->valor : null;
    }
}
