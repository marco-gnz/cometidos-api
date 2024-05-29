<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfigurationResource extends JsonResource
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
            'clave'                     => $this->clave,
            'tipo'                      => $this->tipo,
            'descripcion'               => $this->descripcion,
            'valor'                     => $this->valor,
            'authorized_to_update'      => $this->authorizedToUpdate()
        ];
    }
}
