<?php

namespace App\Http\Resources\User\Solicitud;

use App\Http\Resources\Solicitud\StatusSolicitudResource;
use App\Http\Resources\User\InformeCometido\ListInformeCometidoResource;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class DatosSolicitudResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $fecha_termino = $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null;
        $hora_salida = $this->hora_salida ? Carbon::parse($this->hora_salida)->format('H:i') : null;
        $informe = (object) [
            'exist'                     => $this->informeCometido() ? true : false,
            'message_informe_not'       => "Habilitado desde el $fecha_termino $hora_salida hrs.",
            'is_store'                  => $this->authorizedToCreateInformeCometido(),
            'data'                      => $this->informeCometido() ? ListInformeCometidoResource::make($this->informeCometido()) : null,
            'is_informe_atrasado'       => $this->isInformeAtrasado(),
            'message_informe_atrasado'  => $this->isInformeAtrasado() ? '¡Ingresar Informe de cometido!' : null
        ];

        return [
            'uuid'                      => $this->uuid,
            'codigo'                    => $this->codigo,
            'fecha_inicio'              => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'             => $fecha_termino,
            'hora_llegada'              => $this->hora_llegada ? Carbon::parse($this->hora_llegada)->format('H:i') : null,
            'hora_salida'               => $hora_salida,
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
            'viaja_acompaniante'        => $this->viaja_acompaniante ? "Si" : "No",
            'alimentacion_red'          => $this->alimentacion_red ? "Si" : "No",
            'gastos_alimentacion'       => $this->gastos_alimentacion ? "Si" : "No",
            'gastos_alojamiento'        => $this->gastos_alojamiento ? "Si" : "No",
            'pernocta_lugar_residencia' => $this->pernocta_lugar_residencia ? "Si" : "No",
            'actividad_realizada'       => $this->actividad_realizada ? $this->actividad_realizada : null,
            'observacion'               => $this->observacion,
            'observacion_gastos'        => $this->observacion_gastos ? $this->observacion_gastos : null,
            'last_status_nom'           => EstadoSolicitud::STATUS_NOM[$this->last_status],
            'last_status_type'          => $this->typeLastStatus(),
            'estado_nom'                => Solicitud::STATUS_NOM[$this->status],
            'type_status'               => $this->typeStatus(),
            'motivos'                   => $this->motivos ? $this->motivos->pluck('nombre')->implode(', ') : null,
            'lugares'                   => $this->lugares ? $this->lugares->pluck('nombre')->implode(', ') : null,
            'paises'                    => $this->paises ? $this->paises->pluck('nombre')->implode(', ') : null,
            'transportes'               => $this->transportes ? $this->transportes->pluck('nombre')->implode(', ') : null,
            'documentos_count'          => $this->documentos_count,
            'firmas_status'             => $this->firmas_status ? StatusSolicitudResource::collection($this->firmas_status) : null,
            'tipo_resolucion'           => Solicitud::RESOLUCION_NOM[$this->tipo_resolucion],
            'jornada'                   => Solicitud::JORNADA_NOM[$this->jornada],
            'jornada_abre'              => Solicitud::JORNADA_ABRE[$this->jornada],
            'dentro_pais'               => $this->dentro_pais ? true : false,
            'tipo_comision'             => $this->tipoComision ? $this->tipoComision->nombre : null,
            'created_at'                => $this->fecha_by_user ? Carbon::parse($this->fecha_by_user)->format('d-m-Y H:i:s') : null,
            'user_by'                   => $this->userBy ? $this->userBy->nombre_completo : nul,
            'afecta_convenio'           => $this->afectaConvenio(),
            'url_convenio'              => $this->convenio ? route('convenio.show', ['uuid' => $this->convenio->uuid]) : null,
            'url_resolucion'            => route('resolucioncometidofuncional.show', ['uuid' => $this->uuid]),
            'page_firma'                => $this->pageFirma(),
            'type_page_firma'           => $this->typePageFirma(),
            'is_update'                 => $this->authorizedToUpdate(),
            'documentos'                => $this->exportarDocumentos(),
            'not_actividad'             => $this->isNotActividad(),
            'n_dias_40'                 => $this->n_dias_40,
            'n_dias_100'                => $this->n_dias_100,
            'informe'                   => $informe,
            'is_load_sirh'              => $this->isLoadSirhInfo(),
        ];
    }
}
