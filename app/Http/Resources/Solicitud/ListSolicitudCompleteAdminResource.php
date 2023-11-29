<?php

namespace App\Http\Resources\Solicitud;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Solicitud;
use Carbon\Carbon;

class ListSolicitudCompleteAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $total  = $this->actividades()->sum('actividad_gasto_solicitud.mount');
        return [
            'uuid'                      => $this->uuid,
            'codigo'                    => $this->codigo,
            'fecha_inicio'              => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'             => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'hora_llegada'              => $this->hora_llegada ? Carbon::parse($this->hora_llegada)->format('H:i') : null,
            'hora_salida'               => $this->hora_salida ? Carbon::parse($this->hora_salida)->format('H:i') : null,
            'funcionario_rut'           => $this->funcionario ? $this->funcionario->rut_completo : null,
            'funcionario_nombre'        => $this->funcionario ? $this->funcionario->nombre_completo : null,
            'departamento'              => $this->departamento ? $this->departamento->nombre : null,
            'subdepartamento'           => $this->subdepartamento ? $this->subdepartamento->nombre : null,
            'establecimiento'           => $this->establecimiento ? $this->establecimiento->nombre : null,
            'derecho_pago_value'        => $this->derecho_pago ? true : false,
            'derecho_pago'              => $this->derecho_pago ? "Si" : "No",
            'gastos_alimentacion'       => $this->gastos_alimentacion ? "Si" : "No",
            'gastos_alojamiento'        => $this->gastos_alojamiento ? "Si" : "No",
            'pernocta_lugar_residencia'       => $this->pernocta_lugar_residencia ? "Si" : "No",
            'estado_nom'                => Solicitud::STATUS_NOM[$this->last_status],
            'total_actividades'         => $total ? "$".number_format($total, 0, ",", ".") : null,
            'motivos'                   => $this->motivos ? $this->motivos->pluck('nombre')->implode(', ') : null,
            'lugares'                   => $this->lugares ? $this->lugares->pluck('nombre')->implode(', ') : null,
            'transportes'               => $this->transportes ? $this->transportes->pluck('nombre')->implode(', ') : null,
            'documentos_count'          => $this->documentos_count,
            'firmas_status'             => $this->firmas_status ? StatusSolicitudResource::collection($this->firmas_status) : null,
        ];
    }
}
