<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class HistoryActionUser extends Model
{
    use HasFactory;

    protected $table        = "history_action_users";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'type',
        'data_old',
        'data_new',
        'ip_address',
        'observacion',
        'user_id',
        'send_to_user_id',
        'user_id_by',
        'created_at'
    ];

    public const TYPE_0      = 0;
    public const TYPE_1      = 1;
    public const TYPE_2      = 2;
    public const TYPE_3      = 3;

    public const RESOLUCION_NOM = [
        self::TYPE_0         => 'CREDENCIALES',
        self::TYPE_1         => 'CONTRACTUALES',
        self::TYPE_2         => 'LOGIN',
        self::TYPE_3         => 'SOLICITUD CAMBIO DATOS',
    ];

    protected static function booted()
    {
        static::creating(function ($history) {
            $history->user_id_by   = Auth::check() ? Auth::user()->id : null;
            $history->ip_address   = Request::ip();
        });
    }

    public function userBy()
    {
        return $this->belongsTo(User::class, 'user_id_by');
    }

    public function userSendBy()
    {
        return $this->belongsTo(User::class, 'send_to_user_id');
    }
}
