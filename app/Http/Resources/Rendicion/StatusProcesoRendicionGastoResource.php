<?php

namespace App\Http\Resources\Rendicion;

use App\Models\EstadoProcesoRendicionGasto;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusProcesoRendicionGastoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $is_subrogante = $this->is_subrogante ? '(Subrogante)' : '';
        return [
            'status'                    => $this->status,
            'status_nom'                => EstadoProcesoRendicionGasto::STATUS_NOM[$this->status],
            'estado_type'               => $this->procesoRendicionGasto->typeStatus($this->status),
            'observacion'               => $this->observacion ? $this->observacion : null,
            'user_by'                   => $this->userBy ? $this->userBy->abreNombres() : null,
            'created_at'                => $this->fecha_by_user ? Carbon::parse($this->fecha_by_user)->format('d-m-Y H:i:s') : null,
            'perfil'                    => $this->perfil ? "{$this->perfil->name} {$is_subrogante}"  : null,
        ];
    }
}
