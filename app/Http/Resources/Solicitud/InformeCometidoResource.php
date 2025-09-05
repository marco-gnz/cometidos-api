<?php

namespace App\Http\Resources\Solicitud;

use App\Models\EstadoInformeCometido;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class InformeCometidoResource extends JsonResource
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
            'codigo'                    => $this->codigo ? $this->codigo : null,
            'status_nom'                => EstadoInformeCometido::STATUS_NOM[$this->last_status],
            'status_type'               => EstadoInformeCometido::STATUS_TYPE[$this->last_status],
            'fecha_inicio'              => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-y') : null,
            'fecha_termino'             => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-y') : null,
            'hora_llegada'              => $this->hora_llegada ? Carbon::parse($this->hora_llegada)->format('H:i') : null,
            'hora_salida'               => $this->hora_salida ? Carbon::parse($this->hora_salida)->format('H:i') : null,
        ];
    }
}
