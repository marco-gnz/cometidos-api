<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class UserUpdateResource extends JsonResource
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
            'id'                        => $this->id,
            'uuid'                      => $this->uuid,
            'rut'                       => $this->rut,
            'dv'                        => $this->dv,
            'nombres'                   => $this->nombres,
            'apellidos'                 => $this->apellidos,
            'email'                     => $this->email,
            'establecimiento_id'        => $this->establecimiento_id,
            'departamento_id'           => $this->departamento_id,
            'sub_departamento_id'       => $this->sub_departamento_id,
            'estamento_id'              => $this->estamento_id,
            'cargo_id'                  => $this->cargo_id,
            'calidad_id'                => $this->calidad_id,
            'hora_id'                   => $this->hora_id,
            'ley_id'                    => $this->ley_id,
            'grado_id'                  => $this->grado_id,
        ];
    }
}
