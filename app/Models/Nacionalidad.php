<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nacionalidad extends Model
{
    protected $table        = "nacionalidads";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'nombre'
    ];
}
