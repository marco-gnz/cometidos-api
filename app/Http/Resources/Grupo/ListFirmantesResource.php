<?php

namespace App\Http\Resources\Grupo;

use App\Models\Ausentismo;
use Illuminate\Http\Resources\Json\JsonResource;

class ListFirmantesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $total_ausentismos_hoy = Ausentismo::where('user_ausente_id', $this->funcionario->id)->first();

        return [
            'uuid'                  => $this->uuid,
            'rut'                   => $this->funcionario->rut ? $this->funcionario->rut : null,
            'nombres'               => $this->funcionario->nombre_completo ? $this->funcionario->nombre_completo : null,
            'email'                 => $this->funcionario->email ? $this->funcionario->email : null,
            'posicion_firma'        => $this->posicion_firma,
            'perfil'                => $this->perfil ? $this->perfil->name : null,
            'status'                => $this->status ? true : false,
            'total_ausentismos_hoy' => $total_ausentismos_hoy ? 'Si' : 'No',
            'is_reasignado'         => $this->is_reasignado,
            'is_firma'              => $this->solicitud ? ($this->posicion_firma === $this->solicitud->posicion_firma_actual ? true : false) : false,
            'authorized_to_update'  => $this->authorizedToUpdate(),
            'is_executed'           => $this->is_executed,
            'is_success'            => $this->is_success
        ];
    }
}
