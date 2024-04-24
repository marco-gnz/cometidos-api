<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

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

    public function ajustes()
    {
        return $this->hasMany(CalculoAjuste::class)->orderBy('id', 'DESC');
    }

    public function authorizedToCreate($solicitud)
    {
        return Gate::allows('create', $solicitud);
    }

    public function valorizacionAjuste40()
    {
        $total_dias  = $this->ajustes()->where('active', true)->sum('n_dias_40');
        $total_monto = $this->valor_dia_40 * $total_dias;

        $result = (object) [
            'total_dias'            => number_format($total_dias, 0, ',', '.'),
            'total_monto'           => "$" . number_format($total_monto, 0, ',', '.'),
            'total_monto_value'     => $total_monto
        ];
        return $result;
    }

    public function valorizacionAjuste100()
    {
        $total_dias  = $this->ajustes()->where('active', true)->sum('n_dias_100');
        $total_monto = $this->valor_dia_100 * $total_dias;

        $result = (object) [
            'total_dias'            => number_format($total_dias, 0, ',', '.'),
            'total_monto'           => "$" . number_format($total_monto, 0, ',', '.'),
            'total_monto_value'     => $total_monto
        ];
        return $result;
    }

    public function valorizacionAjusteMonto40()
    {
        $total_monto  = $this->ajustes()->where('active', true)->sum('monto_40');

        $result = (object) [
            'total_monto'           => "$" . number_format($total_monto, 0, ',', '.'),
            'total_monto_value'     => $total_monto
        ];
        return $result;
    }

    public function valorizacionAjusteMonto100()
    {
        $total_monto  = $this->ajustes()->where('active', true)->sum('monto_100');

        $result = (object) [
            'total_monto'           => "$" . number_format($total_monto, 0, ',', '.'),
            'total_monto_value'     => $total_monto
        ];
        return $result;
    }

    public function valorizacionTotalAjusteMonto()
    {
        $a1 = self::valorizacionAjuste40()->total_monto_value;
        $a2 = self::valorizacionAjuste100()->total_monto_value;
        $a3 = self::valorizacionAjusteMonto40()->total_monto_value;
        $a4 = self::valorizacionAjusteMonto100()->total_monto_value;

        $total_40           = $a1 + $a3;
        $total_100          = $a2 + $a4;
        $total_ajustes      = $a1 + $a2 + $a3 + $a4;
        $total_valorizacion = $total_ajustes + $this->monto_total;

        $result = (object) [
            'total_40'                      => "$" . number_format($total_40, 0, ',', '.'),
            'total_100'                     => "$" . number_format($total_100, 0, ',', '.'),
            'total_monto_ajustes'           => "$" . number_format($total_ajustes, 0, ',', '.'),
            'total_valorizacion'            => "$" . number_format($total_valorizacion, 0, ',', '.'),
            'total_valorizacion_value'      => $total_valorizacion
        ];
        return $result;
    }
}
