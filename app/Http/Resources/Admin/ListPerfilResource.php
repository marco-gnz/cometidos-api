<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class ListPerfilResource extends JsonResource
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
            'nombres'           => $this->abreNombres(),
            'email'             => $this->email ? $this->email : null,
            'perfiles'          => $this->getRoleNames()->implode(', '),
            'establecimientos'  => $this->establecimientos ? $this->establecimientos->pluck('sigla')->implode(' - ') : null,
            'ley'               => $this->leyes ? $this->leyes->pluck('nombre')->implode(' - ') : null,
            'deptos'            => $this->departamentos->count(),
            'permisos'          => $this->permissions->count(),
            'authorized_to_update_perfil'  => $this->authorizedToUpdatePerfil(),
            'authorized_to_delete_perfil'  => $this->authorizedToDeletePerfil()
        ];
    }
}
