<?php

namespace App\Http\Resources\User\Contrato;

use App\Http\Resources\Grupo\ListGrupoSelectedResource;
use App\Models\Grupo;
use Illuminate\Http\Resources\Json\JsonResource;

class ListContratosResource extends JsonResource
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
            'ley'                       => $this->ley ? $this->ley->nombre : null,
            'grado'                     => $this->grado ? $this->grado->nombre : 'Sin grado',
            'cargo'                     => $this->cargo ? $this->cargo->nombre : null,
            'departamento'              => $this->departamento ? $this->departamento->nombre : null,
            'sub_departamento'          => $this->subDepartamento ? $this->subDepartamento->nombre : null,
            'establecimiento_sigla'     => $this->establecimiento ? $this->establecimiento->sigla : null,
            'establecimiento'           => $this->establecimiento ? $this->establecimiento->nombre : null,
            'estamento'                 => $this->estamento ? $this->estamento->nombre : null,
            'hora'                      => $this->hora ? "{$this->hora->nombre} hrs." : null,
            'calidad'                   => $this->calidad ? $this->calidad->nombre : null,
            'is_grupo'                  => $this->grupo ? true : false,
            'grupo_id'                  => $this->grupo ? $this->grupo->id : null,
            'grupo_desc'                => $this->grupo ? ListGrupoSelectedResource::make($this->grupo) : null,
            'ley_id'                    => $this->ley_id,
            'estamento_id'              => $this->estamento_id,
            'grado_id'                  => $this->grado_id,
            'cargo_id'                  => $this->cargo_id,
            'departamento_id'           => $this->departamento_id,
            'sub_departamento_id'       => $this->sub_departamento_id,
            'establecimiento_id'        => $this->establecimiento_id,
            'hora_id'                   => $this->hora_id,
            'calidad_id'                => $this->calidad_id,
            'is_posible_grupos'         => ListGrupoSelectedResource::collection($this->isPosibleGrupos()),
            'firmantes'                 => $this->grupo ? ListFirmantesGrupoResource::collection($this->grupo->firmantes()->orderBy('posicion_firma', 'ASC')->get()) : null
        ];
    }
}
