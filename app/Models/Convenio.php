<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Convenio extends Model
{
    protected $table        = "convenios";
    protected $primaryKey   = 'id';

    public const TYPE_COMETIDOS = 0;

    public const TYPE_NOM = [
        self::TYPE_COMETIDOS => 'COMETIDOS'
    ];

    protected $fillable = [
        'uuid',
        'codigo',
        'fecha_inicio',
        'fecha_termino',
        'fecha_resolucion',
        'n_resolucion',
        'n_viatico_mensual',
        'tipo_convenio',
        'anio',
        'observacion',
        'user_id',
        'estamento_id',
        'ley_id',
        'establecimiento_id',
        'ilustre_id',
        'user_id_by',
        'fecha_by_user',
        'user_id_update',
        'fecha_by_user_update',
    ];

    protected static function booted()
    {
        static::creating(function ($convenio) {
            $convenio->uuid                    = Str::uuid();
            $convenio->user_id_by              = Auth::check() ? Auth::user()->id : null;
            $convenio->fecha_by_user           = now();
        });

        static::created(function ($convenio) {
            $convenio->codigo               = self::generarCodigo($convenio);
            $convenio->tipo_convenio        = self::TYPE_COMETIDOS;
            $convenio->save();
        });
    }

    private static function generarCodigo($convenio)
    {
        $correlativo            = str_pad(self::whereYear('created_at', $convenio->created_at->year)->count(), 5, '0', STR_PAD_LEFT);
        $anio                   = $convenio->anio;
        $codigo                 = "{$anio}{$correlativo}";
        return $codigo;
    }

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function estamento()
    {
        return $this->belongsTo(Estamento::class, 'estamento_id');
    }

    public function ley()
    {
        return $this->belongsTo(Ley::class, 'ley_id');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }

    public function ilustre()
    {
        return $this->belongsTo(Ilustre::class, 'ilustre_id');
    }
}
