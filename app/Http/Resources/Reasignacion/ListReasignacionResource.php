<?php

namespace App\Http\Resources\Reasignacion;

use Illuminate\Http\Resources\Json\JsonResource;

class ListReasignacionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $total_take = count($this->solicitudes()->get()->take(1));
        $total_real = count($this->solicitudes()->get());
        $is_plus    = $total_real > $total_take ? true : false;
        return [
            'uuid'                          => $this->uuid,
            'firmante_ausente'              => $this->firmanteAusente ? $this->firmanteAusente->abreNombres() : null,
            'firmante_ausente_email'        => $this->firmanteAusente ? $this->firmanteAusente->email : null,
            'firmante_reasignado'           => $this->firmanteReasignado ? $this->firmanteReasignado->abreNombres() : null,
            'firmante_reasignado_email'     => $this->firmanteReasignado ? $this->firmanteReasignado->email : null,
            'solicitudes'                   => $this->solicitudes ? $this->solicitudes()->take(2)->pluck('codigo')->implode('-') : null,
            'is_plus'                       => $is_plus
        ];
    }
}
