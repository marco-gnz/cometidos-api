<?php

namespace App\Http\Resources\Mantenedores;

use Illuminate\Http\Resources\Json\JsonResource;

class LugaresResource extends JsonResource
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
            'active_value'      => $this->active ? true : false,
            'active_message'    => $this->active ? 'Habilitado' : 'Deshabilitado'
        ];
    }
}
