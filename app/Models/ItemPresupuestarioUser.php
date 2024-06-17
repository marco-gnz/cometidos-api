<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPresupuestarioUser extends Model
{
    use HasFactory;

    protected $table        = "item_presupuestario_users";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'item_presupuestario_id',
        'calidad_id',
        'ley_id'
    ];

    public function calidad()
    {
        return $this->belongsTo(Calidad::class, 'calidad_id');
    }

    public function ley()
    {
        return $this->belongsTo(Ley::class, 'ley_id');
    }

    public function itemNumero()
    {
        return $this->belongsTo(ItemPresupuestario::class, 'item_presupuestario_id');
    }
}
