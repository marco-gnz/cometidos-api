<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EstadoProcesoRendicionGasto extends Model
{
    use HasFactory;

    protected $table        = "estado_proceso_rendicion_gastos";
    protected $primaryKey   = 'id';

    public const STATUS_PENDIENTE   = 0;
    public const STATUS_VERIFICADO  = 1;
    public const STATUS_ANULADO     = 2;

    public const STATUS_NOM = [
        self::STATUS_PENDIENTE      => 'PENDIENTE',
        self::STATUS_VERIFICADO     => 'VERIFICADO',
        self::STATUS_ANULADO        => 'ANULADO',
    ];

    protected $fillable = [
        'uuid',
        'status',
        'observacion',
        'p_rendicion_gasto_id',
        'user_id_by',
        'fecha_by_user',
        'user_id_update',
        'fecha_by_user_update',
    ];

    protected static function booted()
    {
        static::creating(function ($estado) {
            $estado->uuid                    = Str::uuid();
            $estado->user_id_by              = Auth::user()->id;
            $estado->fecha_by_user           = now();
        });
    }

    public function procesoRendicionGasto()
    {
        return $this->belongsTo(ProcesoRendicionGasto::class, 'p_rendicion_gasto_id');
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id_by');
    }
}
