<?php

namespace App\Http\Resources\Solicitud;

use App\Models\Convenio;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ListConvenioResource extends JsonResource
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
            'convenio_id'           => $this->id,
            'codigo'                => $this->codigo ? $this->codigo : null,
            'fecha_inicio'          => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'         => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'fecha_resolucion'      => $this->fecha_resolucion ? Carbon::parse($this->fecha_resolucion)->format('d-m-Y') : null,
            'tipo_convenio'         => Convenio::TYPE_NOM[$this->tipo_convenio],
            'n_resolucion'          => $this->n_resolucion,
            'n_viatico_mensual'     => $this->n_viatico_mensual,
            'observacion'           => $this->observacion ? $this->observacion : null,
            'estamento'             => $this->estamento ? $this->estamento->nombre : null,
            'ley'                   => $this->ley ? $this->ley->nombre : null,
            'establecimiento'       => $this->establecimiento ? $this->establecimiento->nombre : null,
            'ilustre'               => $this->ilustre ? $this->ilustre->nombre : null
        ];
    }
}
