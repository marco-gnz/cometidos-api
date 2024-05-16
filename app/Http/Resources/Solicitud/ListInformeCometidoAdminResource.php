<?php

namespace App\Http\Resources\Solicitud;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\EstadoInformeCometido;
use App\Models\InformeCometido;
use Carbon\Carbon;

class ListInformeCometidoAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $estados = $this->estados()->where('status', '!=', 0)->get();
        return [
            'uuid'                      => $this->uuid,
            'codigo'                    => $this->codigo,
            'fecha_inicio'              => Carbon::parse($this->fecha_inicio)->format('d-m-Y'),
            'fecha_termino'             => Carbon::parse($this->fecha_termino)->format('d-m-Y'),
            'hora_llegada'              => Carbon::parse($this->hora_llegada)->format('H:i'),
            'hora_salida'               => Carbon::parse($this->hora_salida)->format('H:i'),
            'utiliza_transporte'        => $this->utiliza_transporte ? 'Si' : 'No',
            'actividad_realizada'       => $this->actividad_realizada,
            'status_value'              => $this->last_status,
            'status_nom'                => EstadoInformeCometido::STATUS_NOM[$this->last_status],
            'status_type'               => EstadoInformeCometido::STATUS_TYPE[$this->last_status],
            'url'                       => route('informecometido.show', ['uuid' => $this->uuid]),
            'created_at'                => $this->fecha_by_user ? Carbon::parse($this->fecha_by_user)->format('d-m-Y H:i:s') : null,
            'transportes'               => $this->transportes ? $this->transportes->pluck('nombre')->implode(', ') : null,
            'status_ingreso_nom'        => InformeCometido::STATUS_INGRESO_NOM[$this->status_ingreso],
            'status_ingreso_type'       => InformeCometido::STATUS_INGRESO_TYPE[$this->status_ingreso],
            'diff_hours'                => $this->diffPlazoTardioInforme(),
            'estados'                   => $estados ? ListEstadoInformeCometidoAdminResource::collection($estados) : null,
            'authorized_to_aprobar'     => $this->authorizedToAprobar(),
            'authorized_to_rechazar'    => $this->authorizedToRechazar(),
            'authorized_to_update'      => $this->authorizedToUpdate(),
            'authorized_to_delete'      => $this->authorizedToDelete()
        ];
    }
}
