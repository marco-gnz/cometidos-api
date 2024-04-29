<?php

namespace App\Http\Resources\Solicitud;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ListSolicitudReasignarResource extends JsonResource
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
            'codigo'            => $this->codigo,
            'id'                => $this->id,
            'fecha_inicio'      => Carbon::parse($this->fecha_inicio)->format('d-m-Y'),
            'fecha_termino'     => Carbon::parse($this->fecha_termino)->format('d-m-Y'),
        ];
    }
}
