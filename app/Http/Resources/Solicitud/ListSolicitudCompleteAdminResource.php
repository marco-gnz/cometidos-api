<?php

namespace App\Http\Resources\Solicitud;

use App\Models\EstadoSolicitud;
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
            'funcionario_email'         => $this->funcionario ? $this->funcionario->email : null,
            'departamento'              => $this->departamento ? $this->departamento->nombre : null,
            'subdepartamento'           => $this->subdepartamento ? $this->subdepartamento->nombre : null,
            'establecimiento'           => $this->establecimiento ? $this->establecimiento->nombre : null,
            'ley'                       => $this->ley ? $this->ley->nombre : null,
            'grado'                     => $this->grado ? $this->grado->nombre : null,
            'utiliza_transporte'        => $this->utiliza_transporte ? 'Si' : 'No',
            'derecho_pago_value'        => $this->derecho_pago ? true : false,
            'derecho_pago'              => $this->derecho_pago ? "Si" : "No",
            'gastos_alimentacion'       => $this->gastos_alimentacion ? "Si" : "No",
            'gastos_alojamiento'        => $this->gastos_alojamiento ? "Si" : "No",
            'pernocta_lugar_residencia' => $this->pernocta_lugar_residencia ? "Si" : "No",
            'actividad_realizada'       => $this->actividad_realizada ? $this->actividad_realizada : null,
            'observacion_gastos'        => $this->observacion_gastos ? $this->observacion_gastos : null,
            'estado_nom'                => EstadoSolicitud::STATUS_NOM[$this->last_status],
            'total_actividades'         => $total ? "$".number_format($total, 0, ",", ".") : null,
            'motivos'                   => $this->motivos ? $this->motivos->pluck('nombre')->implode(', ') : null,
            'lugares'                   => $this->lugares ? $this->lugares->pluck('nombre')->implode(', ') : null,
            'paises'                    => $this->paises ? $this->paises->pluck('nombre')->implode(', ') : null,
            'transportes'               => $this->transportes ? $this->transportes->pluck('nombre')->implode(', ') : null,
            'documentos_count'          => $this->documentos_count,
            'firmas_status'             => $this->firmas_status ? StatusSolicitudResource::collection($this->firmas_status) : null,
            'fecha_resolucion'          => $this->fecha_resolucion ? Carbon::parse($this->fecha_resolucion)->format('d-m-Y') : null,
            'n_resolucion'              => $this->n_resolucion ? $this->n_resolucion : null,
            'tipo_resolucion'           => Solicitud::RESOLUCION_NOM[$this->tipo_resolucion],
            'jornada'                   => Solicitud::JORNADA_NOM[$this->jornada],
            'dentro_pais'               => $this->dentro_pais ? true : false,
            'n_cargo_user'              => $this->n_cargo_user,
            'tipo_comision'             => $this->tipoComision ? $this->tipoComision->nombre : null,
            'created_at'                => $this->fecha_by_user ? Carbon::parse($this->fecha_by_user)->format('d-m-Y H:i') : null,
            'user_by'                   => $this->userBy ? $this->userBy->nombre_completo : nul,
            'afecta_convenio'           => $this->afecta_convenio !== null ? ($this->afecta_convenio === 1 ? 'AFECTA' : 'NO AFECTA') : null,
            'url_convenio'              => $this->convenio ? route('convenio.show', ['uuid' => $this->convenio->uuid]) : null,
            'url_resolucion'            => route('resolucioncometidofuncional.show', ['uuid' => $this->uuid]),
            'page_firma'                => $this->pageFirma()
        ];
    }
}
