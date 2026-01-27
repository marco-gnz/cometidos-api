<?php

namespace App\Http\Resources\Solicitud;

use App\Models\Documento;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ListSolicitudDocumentosResource extends JsonResource
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
            'url_open'              => $this ? route('documento.show', ['uuid' => $this->uuid]) : null,
            'user_by'               => $this->userBy ? $this->userBy->abreNombres() : null,
            'authorized_to_delete'  => $this->authorizedToDelete(),
            'tipo_carga'            => Documento::MODEL_NOM[$this->model],
            'exist_file'            => Storage::disk('public')->exists($this->url)
        ];
    }
}
