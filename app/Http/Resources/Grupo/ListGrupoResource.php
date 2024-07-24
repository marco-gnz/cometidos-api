<?php

namespace App\Http\Resources\Grupo;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ListGrupoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $n_users = $this->contratos()->get()->unique('user_id')->count();

        return [
            'uuid'              => $this->uuid,
            'id'                => $this->id,
            'codigo'       => $this->codigo,
            'establecimiento'   => $this->establecimiento ? $this->establecimiento->sigla : null,
            'departamento'      => $this->departamento ? $this->departamento->nombre : null,
            'subdepartamento'   => $this->subdepartamento ? $this->subdepartamento->nombre : null,
            'total_firmantes'   => count($this->firmantes),
            'user_by'           => $this->userBy ? $this->userBy->abreNombres() : null,
            'created_at'        => $this->created_at ? Carbon::parse($this->created_at)->format('d-m-Y H:i:s') : null,
            'n_users'           => $n_users,
            'authorized_to_delete'    => $this->authorizedToDelete(),
            'authorized_to_update'    => $this->authorizedToUpdate(),
            'jefatura_directa'          => $this->jefaturaDirecta()
        ];
    }
}
