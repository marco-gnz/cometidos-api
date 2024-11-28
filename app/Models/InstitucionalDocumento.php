<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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

        static::deleting(function ($documento) {
            $url = $documento->url;
            $documento = str_replace('storage/', '', $documento->url);
            Storage::disk('public')->delete($documento);
        });
    }
}
