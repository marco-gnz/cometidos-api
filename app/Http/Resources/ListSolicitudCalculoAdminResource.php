<?php

namespace App\Http\Resources;

use App\Http\Resources\Escala\ListEscalaResource;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ListSolicitudCalculoAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /* $calculo = $this->whenLoaded('calculos')->first(); */
        return [
            'codigo'                => $this->codigo,
            'fecha_inicio'          => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'         => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'derecho_pago_value'    => $this->derecho_pago ? true : false,
            'derecho_pago'          => $this->derecho_pago ? "Si" : "No",
            'calculo_aplicado'      => $this->calculo_aplicado,
            'calculos_count'        => $this->calculos_count,
            'n_dias_40'             => $calculo ? $calculo->n_dias_40  : null,
            'n_dias_100'            => $calculo ? $calculo->n_dias_100 : null,
            'escala'                => $calculo ? ListEscalaResource::make($calculo) : null,
            'monto_40_format'       => $calculo ? "$".number_format($calculo->monto_40, 0, ",", ".") : null,
            'monto_100_format'      => $calculo ? "$".number_format($calculo->monto_100, 0, ",", ".") : null,
            'monto_total'           => $calculo ? "$".number_format($calculo->monto_total, 0, ",", ".") : null,
            'calculo_created_at'    => $calculo ? Carbon::parse($calculo->fecha_by_user)->format('d-m-Y H:i') : null,
            'user_by'               => $calculo ? $calculo->userBy->nombre_completo : null
        ];
    }
}
