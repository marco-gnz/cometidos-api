<?php

namespace App\Http\Resources\Concepto;

use Illuminate\Http\Resources\Json\JsonResource;

class ListConceptoEstablecimientoResource extends JsonResource
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
            'id'                    => $this->id,
            'establecimiento_id'    => $this->establecimiento_id,
            'establecimiento'       => $this->establecimiento ? $this->establecimiento->sigla : null,
            'funcionarios'          => $this->funcionarios ? UsersResource::collection($this->funcionarios) : null
        ];
    }
}
