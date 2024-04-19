<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Establecimiento extends Model
{
    protected $table        = "establecimientos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'cod_sirh',
        'sigla',
        'nombre'
    ];

    public function cicloFirmas()
    {
        return $this->hasMany(CicloFirma::class);
    }
}
