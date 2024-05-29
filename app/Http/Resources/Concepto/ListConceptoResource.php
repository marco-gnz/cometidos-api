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
            'descripcion'                   => $this->descripcion,
            'conceptos_establecimientos'    => ListConceptoEstablecimientoResource::collection($this->whenLoaded('conceptosEstablecimientos')),
            'authorized_to_delete'    => $this->authorizedToDelete(),
            'authorized_to_update'    => $this->authorizedToUpdate(),
            'authorized_to_create'       => $this->authorizedToCreate()
        ];
    }
}
