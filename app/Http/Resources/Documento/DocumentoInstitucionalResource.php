<?php

namespace App\Http\Resources\Documento;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentoInstitucionalResource extends JsonResource
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
            'nombre_file'           => $this->nombre_file,
            'observacion'           => $this->observacion ? $this->observacion : null,
            'size'                  => $this->size,
            'extension'             => $this->extension,
            'created_at'            => $this->created_at ? Carbon::parse($this->created_at)->format('d-m-Y H:i') : null,
            'url'                   => route('documento.institucional.show', ['uuid' => $this->uuid]),
            'user_by'               => $this->userBy ? $this->userBy->abreNombres() : null,
        ];
    }
}
