<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActividadGasto extends Model
{
    protected $table        = "actividad_gastos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'nombre',
        'descripcion',
        'is_particular',
        'status',
        'item_presupuestario_id'
    ];

    public function itemPresupuestario()
    {
        return $this->belongsTo(ItemPresupuestario::class, 'item_presupuestario_id');
    }
}
