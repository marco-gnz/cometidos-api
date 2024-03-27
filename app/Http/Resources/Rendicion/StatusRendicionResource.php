<?php

namespace App\Http\Resources\Rendicion;

use App\Models\RendicionGasto;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusRendicionResource extends JsonResource
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
            'estado_nom'                => RendicionGasto::STATUS_NOM[$this->status],
            'created_at'                => $this->created_at ? Carbon::parse($this->created_at)->format('d-m-Y H:i') : null,
            'user_by'                   => optional($this->userBy)->nombre_completo,
            'observacion'               => $this->observacion ? $this->observacion : null,
            'is_updated_mount'          => $this->is_updated_mount ? true : false
        ];
    }
}
