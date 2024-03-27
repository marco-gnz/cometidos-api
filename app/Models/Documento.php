<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class Documento extends Model
{
    protected $table        = "documentos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'url',
        'nombre',
        'model',
        'size',
        'format',
        'extension',
        'is_valid',
        'solicitud_id',
        'proceso_rendicion_gasto_id',
        'user_id'
    ];

    public const MODEL_SOLICITUD      = 0;
    public const MODEL_RENDICION      = 1;

    protected static function booted()
    {
        static::creating(function ($documento) {
            $documento->uuid                    = Str::uuid();
            $documento->user_id                 =  $documento->user_id;
        });

        static::deleting(function ($documento) {
            $url = $documento->url;
            $documento = str_replace('storage/', '', $documento->url);
            Storage::disk('public')->delete($documento);
        });
    }

    public function procesoRendicionGasto()
    {
        return $this->belongsTo(ProcesoRendicionGasto::class, 'proceso_rendicion_gasto_id');
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'ley_id');
    }

    public function authorizedToDelete()
    {
        return Gate::allows('delete', $this);
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
