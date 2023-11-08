<?php

namespace App\Http\Resources;

use App\Models\Grupo;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAuthResource extends JsonResource
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
            'id'                        => $this->id,
            'rut_completo'              => $this->rut_completo,
            'nombres'                   => $this->nombres,
            'nombre_completo'           => $this->nombre_completo,
            'email'                     => $this->email ? $this->email : null,
            'ley'                       => $this->ley ? $this->ley->nombre : null,
            'grado'                     => $this->grado ? $this->grado->nombre : null,
            'cargo'                     => $this->cargo ? $this->cargo->nombre : null,
            'departamento'              => $this->departamento ? $this->departamento->nombre : null,
            'sub_departamento'          => $this->subDepartamento ? $this->subDepartamento->nombre : null,
            'establecimiento'           => $this->establecimiento ? $this->establecimiento->nombre : null,
            'hora'                      => $this->hora ? $this->hora->nombre : null,
            'calidad'                   => $this->calidad ? $this->calidad->nombre : null,
            'telefono'                  => $this->telefono ? $this->telefono : null,
            'is_group'                  => Grupo::where('departamento_id', $this->departamento->id)->where('sub_departamento_id', $this->subDepartamento->id)->where('establecimiento_id', $this->establecimiento->id)->first() ? true : false,
            'count_solicitudes'         => $this->solicitudes()->where('last_status', 1)->count()
        ];
    }
}
