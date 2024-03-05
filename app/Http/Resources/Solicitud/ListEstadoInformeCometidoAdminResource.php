<?php

namespace App\Http\Resources\Solicitud;

use App\Models\EstadoInformeCometido;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ListEstadoInformeCometidoAdminResource extends JsonResource
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
            'status_nom'                => EstadoInformeCometido::STATUS_NOM[$this->status],
            'status_type'               => EstadoInformeCometido::STATUS_TYPE[$this->status],
            'observacion'               => $this->observacion,
            'user_by'                   => $this->userBy ? $this->userBy->nombre_completo : null,
            'created_at'                => Carbon::parse($this->fecha_by_user)->format('d-m-Y H:i:s')
        ];
    }
}
