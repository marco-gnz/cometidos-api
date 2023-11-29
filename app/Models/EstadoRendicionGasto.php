<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EstadoRendicionGasto extends Model
{
    protected $table        = "estado_rendicion_gastos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'status',
        'observacion',
        'rendicion_gasto_id',
        'rendicion_old',
        'rendicion_new',
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

    public function rendicionGasto()
    {
        return $this->belongsTo(RendicionGasto::class, 'rendicion_gasto_id');
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id_by');
    }
}
