<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoComision extends Model
{
    use HasFactory;

    const METODO_CONSULTA_MOVIMIENTO = 2;
    const METODOS_CONSULTA_SIRH = [self::METODO_CONSULTA_MOVIMIENTO => 'Movimiento'];

    protected $fillable = ['nombre', 'cod_sirh', 'cod_web_service'];
    protected $attributes = ['metodo_consulta' => self::METODO_CONSULTA_MOVIMIENTO];
    protected $casts = ['metodo_consulta' => 'integer'];

    public function consultaSirhInfo()
    {
        return [
            'cod_web_service' => $this->cod_web_service,
            'codigos_web_service' => \App\Services\Sirh\CodigosPermiso::normalizar($this->cod_web_service),
            'metodo_consulta' => $this->metodo_consulta,
            'metodo_consulta_nombre' => self::METODOS_CONSULTA_SIRH[$this->metodo_consulta] ?? null,
        ];
    }

    public function setCodWebServiceAttribute($value)
    {
        $codigos = \App\Services\Sirh\CodigosPermiso::normalizar($value);
        $this->attributes['cod_web_service'] = $codigos ? implode(';', $codigos) : null;
    }

    public function setMetodoConsultaAttribute($value)
    {
        if ((string) $value !== (string) self::METODO_CONSULTA_MOVIMIENTO) {
            throw new \InvalidArgumentException('GECOM solo consulta el método Movimiento.');
        }
        $this->attributes['metodo_consulta'] = self::METODO_CONSULTA_MOVIMIENTO;
    }
}
