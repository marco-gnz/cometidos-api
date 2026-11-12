<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InstitucionalDocumento extends Model
{
    use HasFactory;

    protected $table        = "institucional_documentos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'url',
        'nombre',
        'nombre_file',
        'observacion',
        'model',
        'size',
        'format',
        'extension',
        'is_valid',
        'user_id_by'
    ];

    protected static function booted()
    {
        static::creating(function ($documento) {
            $documento->uuid                    = Str::uuid();
            $documento->user_id_by              = Auth::user()->id;
        });

        static::deleted(function ($documento) {
            \App\Services\Archivos\ArchivoService::eliminarSinReferencias($documento->url);
        });
    }
}
