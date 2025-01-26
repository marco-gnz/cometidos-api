<?php

namespace App\Http\Resources\Rendicion;

use App\Http\Resources\Solicitud\ListSolicitudDocumentosResource;
use App\Http\Resources\User\CuentaBancariaResource;
use App\Models\EstadoProcesoRendicionGasto;
use App\Models\EstadoSolicitud;
use App\Models\ProcesoRendicionGasto;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcesoRendicionGastoDetalleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $particularCondition = function ($q) {
            $q->where('is_particular', true);
        };

        $notParticularCondition = function ($q) {
            $q->where('is_particular', false);
        };

        $getRendiciones = function ($condition) {
            return $this->rendiciones ? $this->rendiciones()->whereHas('actividad', $condition)->get() : null;
        };
        return [
            'uuid'                                          => $this->uuid,
            'n_folio'                                       => $this->n_folio,
            'dias_habiles_pago'                             => $this->dias_habiles_pago,
            'dias_habiles_pago_message'                     => $this->pagoHabilesMessage(),
            'solicitud_derecho_pago_value'                  => $this->solicitud->derecho_pago ? true : false,
            'solicitud_derecho_pago'                        => $this->solicitud->derecho_pago ? "Si" : "No",
            'rut_funcionario'                               => optional($this->solicitud->funcionario)->rut_completo,
            'nombres_funcionario'                           => optional($this->solicitud->funcionario)->nombre_completo,
            'correo_funcionario'                            => optional($this->solicitud->funcionario)->email,
            'establecimiento'                               => optional($this->solicitud->establecimiento)->nombre,
            'departamento'                                  => optional($this->solicitud->departamento)->nombre,
            'subdepartamento'                               => optional($this->solicitud->subdepartamento)->nombre,
            'observacion_solicitud'                         => $this->solicitud->observacion ? $this->solicitud->observacion : null,
            'dentro_pais'                                   => $this->solicitud->dentro_pais ?? false,
            'utiliza_transporte'                            => $this->solicitud->utiliza_transporte ?? false,
            'lugares'                                       => optional($this->solicitud->lugares)->pluck('nombre')->implode(', '),
            'paises'                                        => optional($this->solicitud->paises)->pluck('nombre')->implode(', '),
            'motivos'                                       => optional($this->solicitud->motivos)->pluck('nombre')->implode(', '),
            'n_rendicion'                                   => $this->n_rendicion,
            'created_at'                                    => $this->fecha_by_user ? Carbon::parse($this->fecha_by_user)->format('d-m-Y H:i:s') : null,
            'user_by'                                       => $this->userBy ? $this->userBy->abreNombres() : null,
            'solicitud_codigo'                              => optional($this->solicitud)->codigo,
            'solicitud_fecha_inicio'                        => optional($this->solicitud)->fecha_inicio ? Carbon::parse($this->solicitud->fecha_inicio)->format('d-m-Y') : null,
            'solicitud_fecha_termino'                       => optional($this->solicitud)->fecha_termino ? Carbon::parse($this->solicitud->fecha_termino)->format('d-m-Y') : null,
            'solicitud_transporte'                          => optional($this->solicitud->transportes)->pluck('nombre')->implode(', '),
            'rendiciones_sum_particular'                    => $this->sumRendiciones(true, true, [0, 1, 2], 'mount'),
            'rendiciones_sum_real_particular'               => $this->sumRendiciones(true, true, [1], 'mount_real'),
            'rendiciones_sum_not_particular'                => $this->sumRendiciones(false, true, [0, 1, 2], 'mount'),
            'rendiciones_sum_real_not_particular'           => $this->sumRendiciones(false, true, [1], 'mount_real'),
            'mount_rendiciones_solicitadas'                 => $this->sumRendicionesSolicitadas(),
            'mount_rendiciones_aprobadas'                   => $this->sumRendicionesAprobadas(),
            'count_rendiciones_solicitadas'                 => $this->totalRendicionesSolicitadas(),
            'count_rendiciones_aprobadas'                   => $this->totalRendicionesAprobadas(),
            'rendiciones_particular'                        => $getRendiciones($particularCondition) ? RendicionGastoResource::collection($getRendiciones($particularCondition)) : null,
            'rendiciones_not_particular'                    => $getRendiciones($notParticularCondition) ? RendicionGastoResource::collection($getRendiciones($notParticularCondition)) : null,
            'documentos'                                    => $this->documentos && count($this->documentos) > 0 ? ListSolicitudDocumentosResource::collection($this->documentos) : null,
            'created_at'                                    => $this->created_at ? Carbon::parse($this->created_at)->format('d-m-Y H:i:s') : null,
            'url_gastos_cometido_funcional'                 => $this->uuid ? route('gastoscometidofuncional.show', ['uuid' => $this->uuid]) : null,
            'solicitud_estado_nom'                          => Solicitud::STATUS_NOM[$this->solicitud->status],
            'solicitud_estado_type'                         => $this->solicitud->typeStatus(),
            'solicitud_page_firma'                          => $this->solicitud->pageFirma(),
            'solicitud_type_page_firma'                     => $this->solicitud->typePageFirma(),
            'estado_nom'                                    => EstadoProcesoRendicionGasto::STATUS_NOM[$this->status],
            'estado_type'                                   => $this->typeStatus($this->status),
            'authorized_to_anular'                          => $this->authorizedToAnular(),
            'authorized_to_update_pago'                     => $this->authorizedToUpdatePago(),
            'observacion'                                   => $this->observacion,
            'estados'                                       => $this->estados ? StatusProcesoRendicionGastoResource::collection($this->estados) : null,
            'documentos_r'                                  => $this->exportarDocumentos(),
            'documentos_s'                                  => $this->solicitud->exportarDocumentos(),
            'cuenta_bancaria'                               => $this->cuentaBancaria ? CuentaBancariaResource::make($this->cuentaBancaria) : null,
            'msg_firma'                                     => $this->solicitud->status === Solicitud::STATUS_EN_PROCESO ? 'Esta rendición de gastos se firmará una vez que el cometido complete el proceso de firma y sea procesado.' : null
        ];
    }
}
