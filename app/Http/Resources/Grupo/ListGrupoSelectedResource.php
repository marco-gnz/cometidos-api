<?php

namespace App\Http\Resources\Grupo;

use Illuminate\Http\Resources\Json\JsonResource;

class ListGrupoSelectedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $jefe_directo       = $this->firmantes ? $this->firmantes()->where('role_id', 3)->first() : '';
        $jefe_personal      = $this->firmantes ? $this->firmantes()->where('role_id', 4)->first() : '';
        $codigo             = $this->codigo;
        $establecimiento    = $this->establecimiento ? $this->establecimiento->sigla : null;
        $departamento       = $this->departamento ? $this->departamento->nombre : null;
        $subdepartamento    = $this->subdepartamento ? $this->subdepartamento->nombre : null;
        $jefe_directo_desc  = $jefe_directo ? " - JD: {$jefe_directo->funcionario->abreNombres()}" : null;
        $jefe_personal_desc = $jefe_personal ? " - JP: {$jefe_personal->funcionario->abreNombres()}" : null;
        $descripcion        = "{$codigo} - {$establecimiento} - {$departamento} - {$subdepartamento} {$jefe_directo_desc} {$jefe_personal_desc}";
        return [
            'uuid'              => $this->uuid,
            'id'                => $this->id,
            'codigo'            => $this->codigo,
            'total_firmantes'   => count($this->firmantes),
            'descripcion'       => $descripcion,
            'es_su_grupo'       => $this->es_su_grupo
        ];
    }
}
