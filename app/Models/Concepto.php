<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Concepto extends Model
{
    protected $table        = "conceptos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'nombre',
        'descripcion'
    ];

    protected static function booted()
    {
        static::creating(function ($concepto) {
            $concepto->uuid                    = Str::uuid();
        });
    }

    public function conceptosEstablecimientos()
    {
        return $this->hasMany(ConceptoEstablecimiento::class)->orderBy('id', 'DESC');
    }
}
