<?php

namespace App\Http\Resources\Solicitud;

use App\Models\CalculoAjuste;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ListAjustesCalculoResource extends JsonResource
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
            'tipo_ajuste'           => CalculoAjuste::TYPE_NOM[$this->tipo_ajuste],
            'tipo_ajuste_value'     => $this->tipo_ajuste,
            'n_dias_40'             => $this->n_dias_40 !== null ? number_format($this->n_dias_40, 0, ',', '.') : null,
            'n_dias_100'            => $this->n_dias_100 !== null ? number_format($this->n_dias_100, 0, ',', '.') : null,
            'monto_40'              => $this->monto_40 ? "$" . number_format($this->monto_40, 0, ",", ".") : null,
            'monto_100'             => $this->monto_100 ? "$" . number_format($this->monto_100, 0, ",", ".") : null,
            'type_style'            => $this->typeStyle(),
            'active'                => $this->active ? true : false,
            'observacion'           => $this->observacion,
            'user_by'               => $this->userBy ? $this->userBy->abreNombres() : null,
            'created_at'            => Carbon::parse($this->fecha_by_user)->format('d-m-Y H:i:s')
        ];
    }
}
