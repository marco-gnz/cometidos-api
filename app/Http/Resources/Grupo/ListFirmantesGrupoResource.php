<?php

namespace App\Http\Resources\Grupo;

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
            'uuid'                  => $this->uuid,
            'rut'                   => $this->funcionario->rut ? $this->funcionario->rut : null,
            'nombres'               => $this->funcionario->nombre_completo ? $this->funcionario->nombre_completo : null,
            'email'                 => $this->funcionario->email ? $this->funcionario->email : null,
            'posicion_firma'        => $this->posicion_firma,
            'perfil'                => $this->perfil ? $this->perfil->name : null,
            'status'                => $this->status ? true : false,
            'is_reasignado'         => $this->is_reasignado,
            'is_firma'              => $this->solicitud ? ($this->posicion_firma === $this->solicitud->posicion_firma_actual ? true : false) : false,
            'is_executed'           => $this->is_executed,
            'is_success'            => $this->is_success
        ];
    }
}
