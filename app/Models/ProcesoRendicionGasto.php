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
        'n_rendicion',
        'n_folio',
        'solicitud_id',
        'last_status',
        'user_id_by',
        'fecha_by_user',
        'user_id_update',
        'fecha_by_user_update',
    ];

    protected static function booted()
    {
        static::creating(function ($proceso) {
            $codigo_solicitud                 = $proceso->solicitud->codigo;
            $n_rendicion                      = self::where('solicitud_id', $proceso->solicitud->id)->count() + 1;
            $folio                            = "R-{$n_rendicion}-{$codigo_solicitud}";
            $proceso->n_rendicion             = $n_rendicion;
            $proceso->n_folio                 = $folio;
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

    public function estados()
    {
        return $this->hasMany(EstadoProcesoRendicionGasto::class);
    }

    public function rendicionesfinanzas()
    {
        return $this->hasMany(RendicionGasto::class)
            ->where('rinde_gasto', true)
            ->where('last_status', 1)
            ->orderBy('mount_real', 'DESC');
    }

    public function sumRendicionesfinanzas()
    {
        return $this->hasMany(RendicionGasto::class)
            ->where('rinde_gasto', true)
            ->where('last_status', 1)
            ->sum('mount_real');
    }
}
