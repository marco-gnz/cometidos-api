<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class Ausentismo extends Model
{
    protected $table        = "ausentismos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'fecha_inicio',
        'fecha_termino',
        'user_ausente_id',
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

    public function subrogantes()
    {
        return $this->belongsToMany(User::class);
    }

    public function authorizedToDelete()
    {
        return Gate::allows('delete', $this);
    }

    public function authorizedToUpdate()
    {
        return Gate::allows('update', $this);
    }
}
