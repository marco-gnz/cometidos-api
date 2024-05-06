<?php

namespace App\Http\Resources\Concepto;

use Illuminate\Http\Resources\Json\JsonResource;

class ListConceptoResource extends JsonResource
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
            'id'                            => $this->id,
            'uuid'                          => $this->uuid,
            'nombre'                        => $this->nombre,
            'conceptos_establecimientos'    => ListConceptoEstablecimientoResource::collection($this->whenLoaded('conceptosEstablecimientos'))
        ];
    }
}
