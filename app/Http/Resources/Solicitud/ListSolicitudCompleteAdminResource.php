<?php

namespace App\Http\Resources\Solicitud;

use App\Http\Resources\Grupo\ListGrupoSelectedResource;
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
        $total      = $this->actividades()->sum('actividad_gasto_solicitud.mount');
        $calculo    = $this->getLastCalculo();
        return [
            'uuid'                      => $this->uuid,
            'codigo'                    => $this->codigo,
            'codigo_sirh'               => $this->nResolucionSirh(),
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
            'estamento'                 => $this->estamento ? $this->estamento->nombre : null,
            'cargo'                     => $this->cargo ? $this->cargo->nombre : null,
            'hora'                      => $this->hora ? $this->hora->nombre : null,
            'calidad'                   => $this->calidad ? $this->calidad->nombre : null,
            'utiliza_transporte'        => $this->utiliza_transporte ? 'Si' : 'No',
            'derecho_pago_value'        => $this->derecho_pago ? true : false,
            'derecho_pago'              => $this->derecho_pago ? "Si" : "No",
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
            'total_actividades'         => $total ? "$" . number_format($total, 0, ",", ".") : null,
            'motivos'                   => $this->motivos ? $this->motivos->pluck('nombre')->implode(', ') : null,
            'lugares'                   => $this->lugares ? $this->lugares->pluck('nombre')->implode(', ') : null,
            'paises'                    => $this->paises ? $this->paises->pluck('nombre')->implode(', ') : null,
            'transportes'               => $this->transportes ? $this->transportes->pluck('nombre')->implode(', ') : null,
            'documentos_count'          => $this->documentos_count,
            'firmas_status'             => $this->firmas_status ? StatusSolicitudResource::collection($this->firmas_status) : null,
            'n_resolucion'              => $this->n_resolucion ? $this->n_resolucion : null,
            'tipo_resolucion'           => Solicitud::RESOLUCION_NOM[$this->tipo_resolucion],
            'jornada'                   => Solicitud::JORNADA_NOM[$this->jornada],
            'dentro_pais'               => $this->dentro_pais ? true : false,
            'tipo_comision'             => $this->tipoComision ? $this->tipoComision->nombre : null,
            'created_at'                => $this->fecha_by_user ? Carbon::parse($this->fecha_by_user)->format('d-m-Y H:i:s') : null,
            'user_by'                   => $this->userBy ? $this->userBy->nombre_completo : nul,
            'afecta_convenio'           => $this->afectaConvenio(),
            'url_convenio'              => $this->convenio ? route('convenio.show', ['uuid' => $this->convenio->uuid]) : null,
            'url_resolucion'            => route('resolucioncometidofuncional.show', ['uuid' => $this->uuid]),
            'menu_admin'                => $this->menuAdmin(),
            'page_firma'                => $this->pageFirma(),
            'type_page_firma'           => $this->typePageFirma(),
            'is_update'                 => $this->authorizedToUpdateAdmin(),
            'documentos'                => $this->exportarDocumentos(),
            'not_actividad'             => $this->isNotActividad(),
            'authorized_to_firma'       => $this->authorizedToFirma(),
            'authorized_to_anular'      => $this->authorizedToAnularAdmin(),
            'authorized_to_reasignar_emergency'     => $this->authorizedToReasignarEmergency(),
            'is_grupo'                              => $this->isGrupo(),
            'authorized_to_sincronizar_grupo'       => $this->authorizedToSincronizarGrupo(),
            'authorized_to_create_calculo'             => $this->authorizedToCreateCalculo(),
            'authorized_to_create_calculo_ajuste'      => $this->authorizedToCreateCalculoAjuste(),
            'authorized_to_delete_calculo_ajuste'      => $this->authorizedToDeleteCalculoAjuste(),
            'authorized_to_create_convenio'            => $this->authorizedToCreateConvenio(),
            'is_posible_grupos'                        => $this->isPosibleGrupos() ? ListGrupoSelectedResource::collection($this->isPosibleGrupos()) : null,
            'grupo_id'                                  => $this->grupo ? $this->grupo->id : null,
            'n_dias_40'                 => $this->n_dias_40,
            'n_dias_100'                => $this->n_dias_100,
            'viaja_acompaniante'              => $this->viaja_acompaniante ? "Si" : "No",
            'alimentacion_red'              => $this->alimentacion_red ? "Si" : "No",
            'load_sirh'                     => $this->load_sirh ? true : false,
            'authorized_to_load_sirh'       => $this->authorizedToLoadSirh(),
            'is_load_sirh'                  => $this->isLoadSirhInfo(),
            'last_mov_load_sirh'            => $this->lastMovLoadSirh() ? LoadSirhMovResource::make($this->lastMovLoadSirh()) : null,
            'n_contacto'                    => $this->n_contacto,
            'email'                         => $this->email,
            'fecha_nacimiento'              => $this->funcionario->fecha_nacimiento ? Carbon::parse($this->funcionario->fecha_nacimiento)->format('d-m-Y') : null,
            'nacionalidad'                  => $this->nacionalidad ? $this->nacionalidad->nombre : null,
            'calculo'                       => $calculo ? ListCalculoResoruce::make($calculo) : null
        ];
    }
}
