<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class EditPerfilResource extends JsonResource
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
            'uuid'                      => $this->uuid,
            'perfiles_id'               => $this->roles ? $this->roles->pluck('id')->toArray() : [],
            'permissions_id'            => $this->permissions ? $this->permissions->pluck('id')->toArray() : [],
            'establecimientos_id'       => $this->establecimientos ? $this->establecimientos->pluck('id')->toArray() : null,
            'leys_id'                   => $this->leyes ? $this-> leyes->pluck('id')->toArray() : null,
            'deptos_id'                 => $this->departamentos ? $this->departamentos->pluck('id')->toArray() : null,
        ];
    }
}
