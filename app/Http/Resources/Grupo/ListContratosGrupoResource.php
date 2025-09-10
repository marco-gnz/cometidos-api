<?php

namespace App\Http\Resources\Grupo;

use Illuminate\Http\Resources\Json\JsonResource;

class ListContratosGrupoResource extends JsonResource
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
            'ley'               => $this->ley ? $this->ley->nombre : null,
            'departamento'      => $this->departamento ? $this->departamento->nombre : null,
            'establecimiento'   => $this->establecimiento ? $this->establecimiento->sigla : null,
            'funcionario_nombres'       => $this->funcionario ? $this->funcionario->nombre_completo : null,
            'funcionario_rut'       => $this->funcionario ? $this->funcionario->rut_completo : null
        ];
    }
}
