<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Reasignacion extends Model
{
    protected $table        = "reasignacions";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'fecha_inicio',
        'fecha_termino',
        'user_ausente_id',
        'user_subrogante_id',
        'user_id_by',
        'fecha_by_user',
    ];

    protected static function booted()
    {
        static::creating(function ($ausentismo) {
            $ausentismo->uuid                   = Str::uuid();
            $ausentismo->user_id_by             = Auth::user()->id;
            $ausentismo->fecha_by_user          = now();
        });
    }

    public function firmanteAusente()
    {
        return $this->belongsTo(User::class, 'user_ausente_id');
    }

    public function firmanteReasignado()
    {
        return $this->belongsTo(User::class, 'user_subrogante_id');
    }

    public function solicitudes()
    {
        return $this->belongsToMany(Solicitud::class);
    }
}
