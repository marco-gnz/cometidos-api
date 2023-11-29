<?php

namespace App\Http\Resources\Solicitud;

use Illuminate\Http\Resources\Json\JsonResource;

class ListActividadesResource extends JsonResource
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
            'id'                => $this->id,
            'nombre'            => $this->nombre,
            'mount'             => $this->pivot->mount ? $this->pivot->mount : null,
            'mount_format'      => $this->pivot->mount ? "$".number_format($this->pivot->mount, 0, ",", ".") : null,
            'status_admin'      => $this->pivot->status_admin ? true : false,
            'rinde_gastos_servicio'=> $this->pivot->rinde_gastos_servicio ? true : false
        ];
    }
}
