<?php

namespace App\Http\Resources\Grupo;

use Illuminate\Http\Resources\Json\JsonResource;

class ListGrupoResource extends JsonResource
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
            'uuid'              => $this->uuid,
            'establecimiento'   => $this->establecimiento ? $this->establecimiento->sigla : null,
            'departamento'      => $this->departamento ? $this->departamento->nombre : null,
            'subdepartamento'   => $this->subdepartamento ? $this->subdepartamento->nombre : null,
            'total_firmantes'   => count($this->firmantes),
            'firmantes'         => $this->firmantes ? ListFirmantesResource::collection($this->firmantes) : null
        ];
    }
}
