<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Convenio extends Model
{
    protected $table        = "convenios";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'uuid',
        'codigo',
        'fecha_inicio',
        'fecha_termino',
        'fecha_resolucion',
        'n_resolucion',
        'n_viatico_mensual',
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
