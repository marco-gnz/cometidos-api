<?php

namespace App\Http\Resources\User\Contrato;

use Illuminate\Http\Resources\Json\JsonResource;

class ListFirmantesGrupoResource extends JsonResource
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
            'posicion_firma'        => $this->posicion_firma,
            'nombres'               => $this->funcionario ? $this->funcionario->abreNombres() : null,
            'email'                 => $this->funcionario->email ? $this->funcionario->email : null,
            'perfil'                => $this->perfil ? $this->perfil->name : null,
            'perfil_id'             => $this->perfil ? $this->perfil->id : null,
        ];
    }
}
