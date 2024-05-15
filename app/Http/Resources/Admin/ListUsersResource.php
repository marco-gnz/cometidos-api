<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class ListUsersResource extends JsonResource
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
            'rut'               => $this->rut_completo,
            'nombres'           => "{$this->apellidos} {$this->nombres}",
            'establecimiento'   => $this->establecimiento ? $this->establecimiento->sigla : null,
            'depto'             => $this->departamento ? $this->departamento->nombre : null,
            'grado'             => $this->grado ? $this->grado->nombre : null,
            'ley'               => $this->ley ? $this->ley->nombre : null,
            'status'            => $this->estado ? true : false
        ];
    }
}
