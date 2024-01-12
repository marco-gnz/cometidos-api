<?php

namespace App\Http\Resources\Solicitud;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PropuestaCalculoSolicitud extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'fecha_inicio'              => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'             => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'anio'                      => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('Y') : null,
            'ley'                       => $this->ley ? $this->ley->nombre : null,
            'grado'                     => $this->grado ? $this->grado->nombre : null,
            'derecho_pago_value'        => $this->derecho_pago ? true : false,
            'derecho_pago'              => $this->derecho_pago ? "Si" : "No",
            'n_dias_40'                 => $this->n_dias_40 ? $this->n_dias_40 : null,
            'n_dias_100'                => $this->n_dias_100 ? $this->n_dias_100 : null,
            'monto_40_format'           => $this->monto_40 ? "$" . number_format($this->monto_40, 0, ",", ".") : null,
            'monto_100_format'          => $this->monto_100 ? "$" . number_format($this->monto_100, 0, ",", ".") : null,
            'monto_total'               => $this->monto_total ? "$" . number_format($this->monto_total, 0, ",", ".") : null,
            'is_escala'                 => $this->is_escala != null ? ($this->is_escala ? true : false) : null
        ];
    }
}
