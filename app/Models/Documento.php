<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Documento extends Model
{
    protected $table        = "documentos";
    protected $primaryKey   = 'id';

    protected $fillable = [
       'url',
       'nombre',
       'size',
       'format',
       'extension',
       'is_valid',
       'solicitud_id',
       'user_id'
    ];

    protected static function booted()
    {
        static::creating(function ($documento) {
            $documento->uuid                    = Str::uuid();
            $documento->user_id                 =  $documento->user_id;
        });
    }
}
