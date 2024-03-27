<?php

namespace App\Http\Resources\Rendicion;

use Illuminate\Http\Resources\Json\JsonResource;

class ProcesoRendicionGastoRendicionesUpdateResource extends JsonResource
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
            'id'                    => $this->actividad->id,
            'nombre'                => $this->actividad->nombre,
            'rinde_gasto'           => $this->rinde_gasto ? 1 : 0,
            'mount'                 => $this->mount,
            'rinde_gastos_servicio' => $this->rinde_gastos_servicio != null ? ($this->rinde_gastos_servicio ? 1 : 0) : null
        ];
    }
}
