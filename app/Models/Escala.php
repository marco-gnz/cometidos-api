<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Escala extends Model
{
    protected $table        = "escalas";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'fecha_inicio',
        'fecha_termino',
        'valor_dia_40',
        'valor_dia_100',
        'ley_id',
        'grado_id'
    ];

    protected static function booted()
    {
        static::creating(function ($escala) {
            $escala->uuid                    = Str::uuid();
        });
    }

    public function ley()
    {
        return $this->belongsTo(Ley::class, 'ley_id');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }
}
