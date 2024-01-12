<?php

namespace App\Http\Resources\Rendicion;

use App\Http\Resources\Solicitud\ListSolicitudDocumentosResource;
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
        $rendiciones_sum = $this->rendiciones->where('rinde_gasto', true)->where('last_status', 1)->sum('mount_real');
        return [
            'uuid'                              => $this->uuid,
            'solicitud_codigo'                  => $this->solicitud ? $this->solicitud->codigo : null,
            'solicitud_fecha_inicio'            => $this->solicitud ? Carbon::parse($this->solicitud->fecha_inicio)->format('d-m-Y'): null,
            'solicitud_fecha_termino'           => $this->solicitud ? Carbon::parse($this->solicitud->fecha_termino)->format('d-m-Y') : null,
            'solicitud_transporte'              => $this->solicitud ? $this->solicitud->transportes->pluck('nombre')->implode(', ') : null,
            'rendiciones_count'                 => $this->rendiciones->where('rinde_gasto', true)->where('last_status', 1)->count(),
            'rendiciones_sum'                   => $rendiciones_sum,
            'rendiciones_sum_format'            => "$".number_format($rendiciones_sum, 0, ",", "."),
            'rendiciones'                       => $this->rendiciones ? RendicionGastoResource::collection($this->rendiciones) : null,
            'documentos'                        => $this->documentos && count($this->documentos) > 0 ? ListSolicitudDocumentosResource::collection($this->documentos) : null,
            'created_at'                        => $this->created_at ? Carbon::parse($this->created_at)->format('d-m-Y H:i') : null
        ];
    }
}