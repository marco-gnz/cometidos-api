<?php

namespace App\Http\Resources\Convenio;

use App\Models\Convenio;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ListConvenioResource extends JsonResource
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
            'uuid'                  => $this->uuid,
            'codigo'                => $this->codigo ? $this->codigo : null,
            'active_value'          => $this->active ? true : false,
            'active_message'        => $this->active ? 'Habilitado' : 'Deshabilitado',
            'fecha_inicio'          => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'         => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'fecha_resolucion'      => $this->fecha_resolucion ? Carbon::parse($this->fecha_resolucion)->format('d-m-Y') : null,
            'n_resolucion'          => $this->n_resolucion,
            'ley'                   => $this->ley ? $this->ley->nombre : null,
            'establecimiento'       => $this->establecimiento ? $this->establecimiento->nombre : null,
            'ilustre'               => $this->ilustre ? $this->ilustre->nombre : null,
            'funcionario'           => $this->funcionario ? $this->funcionario->nombre_completo : null,
            'authorized_to_delete'    => $this->authorizedToDelete(),
            'authorized_to_update'    => $this->authorizedToUpdate(),
            'n_solicitudes'           => $this->solicitudes()->count()
        ];
    }
}
