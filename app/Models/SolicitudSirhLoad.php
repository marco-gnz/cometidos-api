<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class SolicitudSirhLoad extends Model
{
    use HasFactory;

    protected $table        = "solicitud_sirh_loads";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'load_sirh',
        'ip_address',
        'solicitud_id',
        'user_id'
    ];

    protected static function booted()
    {
        static::creating(function ($load) {
            $load->user_id      = Auth::check() ? Auth::user()->id : NULL;
            $load->ip_address   = Request::ip();
        });

        static::created(function ($load){
            $load->solicitud->update([
                'load_sirh' => $load->load_sirh
            ]);
        });
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
