<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lugar extends Model
{
    protected $table        = "lugars";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'nombre',
        'active'
    ];

    protected static function booted()
    {
        static::creating(function ($lugar) {
            $lugar->nombre = strtoupper($lugar->nombre);
        });
    }

    public function scopeInput($query, $params)
    {
        if ($params)
            return $query->where('nombre', 'like', '%' . $params . '%');
    }

    public function scopeStatus($query, $params)
    {
        if ($params)
            return $query->whereIn('active', $params);
    }
}
