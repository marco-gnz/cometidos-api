<?php

namespace App\Http\Resources\Solicitud;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'uuid'          => $this->uuid,
            'url'           => $this->url,
            'nombre'        => $this->nombre,
            'size'          => $this->size,
            'extension'     => $this->extension,
            'created_at'    => $this->created_at ? Carbon::parse($this->created_at)->format('d-m-Y H:i:s') : null
        ];
    }
}
