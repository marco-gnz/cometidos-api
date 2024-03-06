<?php

namespace App\Http\Resources\User\InformeCometido;

use App\Models\EstadoInformeCometido;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ListInformeCometidoResource extends JsonResource
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
            'uuid'                      => $this->uuid,
            'codigo'                    => $this->codigo,
            'fecha_inicio'              => Carbon::parse($this->fecha_inicio)->format('d-m-Y'),
            'fecha_termino'             => Carbon::parse($this->fecha_termino)->format('d-m-Y'),
            'hora_llegada'              => Carbon::parse($this->hora_llegada)->format('H:i'),
            'hora_salida'               => Carbon::parse($this->hora_salida)->format('H:i'),
            'utiliza_transporte'        => $this->utiliza_transporte ? 'Si' : 'No',
            'actividad_realizada'       => $this->actividad_realizada,
            'status_nom'                => EstadoInformeCometido::STATUS_NOM[$this->last_status],
            'status_type'               => EstadoInformeCometido::STATUS_TYPE[$this->last_status],
            'url'                       => route('informecometido.show', ['uuid' => $this->uuid]),
            'created_at'                => $this->fecha_by_user ? Carbon::parse($this->fecha_by_user)->format('d-m-Y H:i:s') : null,
            'is_view'                   => $this->authorizedToView()
        ];
    }
}
