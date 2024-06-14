<?php

namespace App\Http\Resources\Rendicion;

use App\Http\Resources\Solicitud\ListSolicitudDocumentosResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcesoRendicionGastoUpdateResource extends JsonResource
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
            'n_folio'                           => $this->n_folio,
            'observacion'                       => $this->observacion,
            'actividades'                       => $this->rendiciones ? ProcesoRendicionGastoRendicionesUpdateResource::collection($this->rendiciones) : null,
            'is_avion'                          => $this->transportes ? $this->transportes()->where('solicitud_transporte.transporte_id', 1)->exists() : false,
            'documentos'                        => $this->documentos ? ListSolicitudDocumentosResource::collection($this->documentos) : []
        ];
    }
}
