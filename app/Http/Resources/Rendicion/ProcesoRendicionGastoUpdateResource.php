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
        $is_avion = $this->solicitud->transportes()->where('solicitud_transporte.transporte_id', 1)->exists();
        $rendiciones = $this->rendiciones ? $this->rendiciones()->get() : null;
        return [
            'uuid'                              => $this->uuid,
            'n_folio'                           => $this->n_folio,
            'observacion'                       => $this->observacion,
            'actividades'                       => $rendiciones ? ProcesoRendicionGastoRendicionesUpdateResource::collection($rendiciones) : null,
            'is_avion'                          => $is_avion === true ? 1 : 0,
            'documentos'                        => $this->documentos ? ListSolicitudDocumentosResource::collection($this->documentos) : []
        ];
    }
}
