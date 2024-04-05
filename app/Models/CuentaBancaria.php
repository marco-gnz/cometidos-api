<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CuentaBancaria extends Model
{
    protected $table        = "cuenta_bancarias";
    protected $primaryKey   = 'id';

    public const TYPE_ACCOUNT_1 = 1;
    public const TYPE_ACCOUNT_2 = 2;
    public const TYPE_ACCOUNT_3 = 3;
    public const TYPE_ACCOUNT_4 = 4;
    public const TYPE_ACCOUNT_5 = 5;

    public const TYPE_ACCOUNT_NOM = [
        self::TYPE_ACCOUNT_1 => 'Cuenta Vista',
        self::TYPE_ACCOUNT_2 => 'Cuenta Ahorro',
        self::TYPE_ACCOUNT_3 => 'Cuenta Corriente',
        self::TYPE_ACCOUNT_4 => 'Cuenta RUT',
        self::TYPE_ACCOUNT_5 => 'Chquera Electronica',
    ];

    protected $fillable = [
        'uuid',
        'tipo_cuenta',
        'n_cuenta',
        'status',
        'banco_id',
        'user_id',
        'user_id_by',
        'fecha_by_user'
    ];

    protected static function booted()
    {
        static::creating(function ($cuenta) {
            $cuenta->uuid                    = Str::uuid();
            $cuenta->user_id_by              = Auth::check() ? Auth::user()->id : null;
            $cuenta->fecha_by_user           = now();
        });
    }

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'banco_id');
    }
}
