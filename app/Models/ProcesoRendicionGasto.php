<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProcesoRendicionGasto extends Model
{
    protected $table        = "proceso_rendicion_gastos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'n_rendicion',
        'solicitud_id',
        'user_id_by',
        'fecha_by_user',
        'user_id_update',
        'fecha_by_user_update',
    ];

    protected static function booted()
    {
        static::creating(function ($proceso) {
            $proceso->n_rendicion             = self::where('solicitud_id', $proceso->solicitud->id)->count()+1;
            $proceso->uuid                    = Str::uuid();
            $proceso->user_id_by              = Auth::user()->id;
            $proceso->fecha_by_user           = now();
        });
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function rendiciones()
    {
        return $this->hasMany(RendicionGasto::class)->orderBy('rinde_gasto', 'DESC');
    }

    public function addRendiciones(array $data)
    {
        return $this->rendiciones()->createMany($data);
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class);
    }
}
