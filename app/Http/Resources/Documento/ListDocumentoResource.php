<?php

namespace App\Http\Resources\Documento;

use App\Models\Documento;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ListDocumentoResource extends JsonResource
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
            'uuid'                  => $this->uuid,
            'url'                   => $this->url,
            'nombre'                => $this->nombre,
            'nombre_complete'       => $this->nombre,
            'size'                  => $this->size,
            'extension'             => $this->extension,
            'created_at'            => $this->created_at ? Carbon::parse($this->created_at)->format('d-m-Y H:i:s') : null,
            'url_open'              => route('documento.show', ['uuid' => $this->uuid]),
            'user_by'               => $this->userBy ? $this->userBy->abreNombres() : null,
            'model'                 => $this->model,
            'tipo_carga'            => Documento::MODEL_NOM[$this->model],
            'solicitud'             => $this->solicitud ? $this->solicitud->codigo : null,
            'rendicion'             => $this->procesoRendicionGasto ? $this->procesoRendicionGasto->n_folio : null,
        ];
    }
}
