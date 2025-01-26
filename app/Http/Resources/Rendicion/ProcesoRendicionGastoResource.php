<?php

namespace App\Http\Resources\Rendicion;

use App\Models\EstadoProcesoRendicionGasto;
use App\Models\EstadoSolicitud;
use App\Models\ProcesoRendicionGasto;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcesoRendicionGastoResource extends JsonResource
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
            'uuid'                              => $this->uuid,
            'n_rendicion'                       => $this->n_rendicion,
            'n_folio'                           => $this->n_folio,
            'solicitud_uuid'                    => $this->solicitud ? $this->solicitud->uuid : null,
            'solicitud_codigo'                  => $this->solicitud ? $this->solicitud->codigo : null,
            'solicitud_fecha_inicio'            => $this->solicitud ? Carbon::parse($this->solicitud->fecha_inicio)->format('d-m-y') : null,
            'solicitud_fecha_termino'           => $this->solicitud ? Carbon::parse($this->solicitud->fecha_termino)->format('d-m-y') : null,
            'funcionario'                       => $this->solicitud ? $this->solicitud->funcionario->abreNombresList() : null,
            'establecimiento'                   => $this->solicitud ? $this->solicitud->establecimiento->sigla : null,
            'lugares'                           => optional($this->solicitud->lugares)->pluck('nombre')->implode(', '),
            'mount_rendiciones_solicitadas'     => $this->sumRendicionesSolicitadas(),
            'mount_rendiciones_aprobadas'       => $this->sumRendicionesAprobadas(),
            'solicitud_estado_nom'              => Solicitud::STATUS_NOM[$this->solicitud->status],
            'solicitud_estado_type'             => $this->solicitud->typeStatus(),
            'solicitud_page_firma'              => $this->solicitud->pageFirma(),
            'solicitud_type_page_firma'         => $this->solicitud->typePageFirma(),
            'estado_nom'                        => EstadoProcesoRendicionGasto::STATUS_NOM[$this->status],
            'estado_type'                       => $this->typeStatus($this->status),
            'authorized_to_delete'              => $this->authorizedToDelete(),
            'authorized_to_update'              => $this->authorizedToUpdate(),
            'authorized_to_anular'              => $this->authorizedToAnular(),
            'authorized_to_aprobar'             => $this->authorizedToAprobar(),
            'authorized_to_rechazar'            => $this->authorizedToRechazar(),
            'is_rendiciones_modificadas'        => $this->isRendicionesModificadas(),
            'created_at'                        => Carbon::parse($this->created_at)->format('d-m-Y H:i')
        ];
    }
}
