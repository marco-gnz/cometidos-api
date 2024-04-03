<?php

namespace App\Http\Resources\Firmante;

use Illuminate\Http\Resources\Json\JsonResource;

class ListFirmanteResource extends JsonResource
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
            'uuid'                  => $this->uuid,
            'rut'                   => $this->funcionario->rut ? $this->funcionario->rut : null,
            'nombres'               => $this->funcionario->nombre_completo ? $this->funcionario->nombre_completo : null,
            'email'                 => $this->funcionario->email ? $this->funcionario->email : null,
            'posicion_firma'        => $this->posicion_firma,
            'perfil'                => $this->perfil ? $this->perfil->name : null,
            'status'                => $this->status ? true : false,
        ];
    }
}
