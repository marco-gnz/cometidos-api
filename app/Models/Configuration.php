<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $table        = "configurations";
    protected $primaryKey   = 'id';

    protected $fillable = ['clave', 'valor', 'tipo', 'descripcion','establecimiento_id'];

    public static function obtenerValor($clave, $establecimiento_id)
    {
        $configuracion = self::where('clave', $clave)->where('establecimiento_id', $establecimiento_id)->first();
        return $configuracion ? $configuracion->valor : null;
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }
}
