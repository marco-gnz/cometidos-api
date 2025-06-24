<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Request;

class CalculoAjuste extends Model
{
    protected $table        = "calculo_ajustes";
    protected $primaryKey   = 'id';

    public const TYPE_0     = 0;
    public const TYPE_1     = 1;

    public const TYPE_NOM = [
        self::TYPE_0        => 'AJUSTE DE DÍAS',
        self::TYPE_1        => 'AJUSTE DE MONTO',
    ];

    protected $fillable = [
        'uuid',
        'tipo_ajuste',
        'n_dias_40',
        'n_dias_100',
        'monto_40',
        'monto_100',
        'active',
        'ip_address',
        'fecha_by_user',
        'observacion',
        'soliucitud_calculo_id',
        'user_id_by',
        'user_id_update',
        'fecha_by_user_update'
    ];

    protected static function booted()
    {
        static::creating(function ($ajuste) {
            $ajuste->uuid                   = Str::uuid();
            $ajuste->user_id_by             = Auth::user()->id;
            $ajuste->ip_address             = Request::ip();
            $ajuste->fecha_by_user          = now();
        });

        static::created(function ($ajuste) {
            $total_valorizacion_value = $ajuste->calculo->valorizacionTotalAjusteMonto()->total_valorizacion_value ?? 0;
            $ajuste->calculo->update(['monto_total_pagar' => $total_valorizacion_value]);
        });

        static::deleted(function ($ajuste) {
            $total_valorizacion_value = $ajuste->calculo->valorizacionTotalAjusteMonto()->total_valorizacion_value ?? 0;
            $ajuste->calculo->update(['monto_total_pagar' => $total_valorizacion_value]);
        });
    }

    public function estados()
    {
        return $this->hasMany(EstadoCalculoAjuste::class)->orderBy('id', 'DESC');
    }

    public function calculo()
    {
        return $this->belongsTo(SoliucitudCalculo::class, 'soliucitud_calculo_id');
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id_by');
    }

    public function addEstados(array $estados)
    {
        return $this->estados()->createMany($estados);
    }

    public function typeStyle()
    {
        if ($this->active) {
            return 'success';
        } else {
            return 'danger';
        }
    }
}
