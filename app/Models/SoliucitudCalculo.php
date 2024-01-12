<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SoliucitudCalculo extends Model
{
    protected $table        = "soliucitud_calculos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'fecha_inicio',
        'fecha_termino',
        'n_dias_40',
        'n_dias_100',
        'valor_dia_40',
        'valor_dia_100',
        'monto_40',
        'monto_100',
        'monto_total',
        'solicitud_id',
        'ley_id',
        'grado_id',
        'user_id_by',
        'fecha_by_user'
    ];

    protected static function booted()
    {
        static::creating(function ($calculo) {
            $calculo->monto_total             = $calculo->monto_40 + $calculo->monto_100;
            $calculo->uuid                    = Str::uuid();
            $calculo->fecha_by_user           = now();
            $calculo->user_id_by              = Auth::user()->id;
        });
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function ley()
    {
        return $this->belongsTo(Ley::class, 'ley_id');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id_by');
    }
}
