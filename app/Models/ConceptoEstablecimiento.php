<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConceptoEstablecimiento extends Model
{
    protected $table        = "concepto_establecimientos";
    protected $primaryKey   = 'id';

    protected $fillable = [
        'concepto_id',
        'establecimiento_id'
    ];

    public function concepto()
    {
        return $this->belongsTo(Concepto::class, 'concepto_id');
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }

    public function funcionarios()
    {
        return $this->belongsToMany(User::class)->withPivot('posicion', 'active')->orderBy('posicion', 'ASC');
    }
}
