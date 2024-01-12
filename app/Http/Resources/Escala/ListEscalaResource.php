<?php

namespace App\Http\Resources\Escala;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ListEscalaResource extends JsonResource
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
            'fecha_inicio'          => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'         => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'valor_dia_40_format'   => "$".number_format($this->valor_dia_40, 0, ",", "."),
            'valor_dia_100_format'  => "$".number_format($this->valor_dia_100, 0, ",", "."),
            'ley'                   => $this->ley ? $this->ley->nombre : null,
            'grado'                 => $this->grado ? $this->grado->nombre : null,
            'is_selected'           => $this->is_selected != null ? ($this->is_selected ? true : false) : null
        ];
    }
}
