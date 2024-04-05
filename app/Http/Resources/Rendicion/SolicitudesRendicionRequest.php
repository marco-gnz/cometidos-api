<?php

namespace App\Http\Resources\Rendicion;

use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitudesRendicionRequest extends JsonResource
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
            'fecha_inicio'              => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'             => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'hora_llegada'              => $this->hora_llegada ? Carbon::parse($this->hora_llegada)->format('H:i') : null,
            'hora_salida'               => $this->hora_salida ? Carbon::parse($this->hora_salida)->format('H:i') : null,
            'departamento_complete'     => $this->departamento ? $this->departamento->nombre : null,
            'subdepartamento_complete'  => $this->subdepartamento ? $this->subdepartamento->nombre : null,
            'establecimiento'           => $this->establecimiento ? $this->establecimiento->sigla : null,
            'derecho_pago'              => $this->derecho_pago ? "Si" : "No",
            'jornada'                   => Solicitud::JORNADA_NOM[$this->jornada],
            'tipo_comision'             => $this->tipoComision ? $this->tipoComision->nombre : null,
            'estado_nom'                => Solicitud::STATUS_NOM[$this->status],
            'is_avion'                  => $this->transportes ? $this->transportes()->where('solicitud_transporte.transporte_id', 1)->exists() : false
        ];
    }
}
