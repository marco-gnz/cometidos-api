<?php

namespace App\Http\Resources\Solicitud;

use Illuminate\Http\Resources\Json\JsonResource;

class InformeCometidoUpdateResource extends JsonResource
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
            'uuid'                      => $this->uuid,
            'codigo'                    => $this->codigo,
            'fecha_informe'             => [$this->fecha_inicio, $this->fecha_termino],
            'hora_informe'              => [$this->hora_llegada, $this->hora_salida],
            'utiliza_transporte'        => $this->utiliza_transporte ? 1 : 0,
            'medio_transporte'          => $this->transportes ? $this->transportes->pluck('id')->toArray() : [],
            'actividad_realizada'       => $this->actividad_realizada
        ];
    }
}
