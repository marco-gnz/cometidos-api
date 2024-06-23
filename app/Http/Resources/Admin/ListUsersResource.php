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
            'email'             => $this->email ? $this->email : null,
            'status'            => $this->estado ? true : false,
            'authorized_to_update'    => $this->authorizedToUpdate()
        ];
    }
}
