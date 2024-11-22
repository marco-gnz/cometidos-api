<?php

namespace App\Http\Resources\Concepto;

use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

class UsersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $role = Role::find($this->pivot->role_id);
        return [
            'nombres'   => $this->abreNombres(),
            'email'     => $this->email,
            'posicion'  => $this->pivot->posicion,
            'active'    => $this->pivot->active,
            'perfil'    => $role ? $role->name : null
        ];
    }
}
