<?php

namespace App\Http\Resources\Solicitud;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ListCalculoResoruce extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $result = (object) [
            'valorizacion_ajuste_40'        => $this->valorizacionAjuste40(),
            'valorizacion_ajuste_100'       => $this->valorizacionAjuste100(),
            'valorizacion_ajuste_monto_40'  => $this->valorizacionAjusteMonto40(),
            'valorizacion_ajuste_monto_100' => $this->valorizacionAjusteMonto100(),
            'valorizacion_ajuste_monto'       => $this->valorizacionTotalAjusteMonto()
        ];
        return [
            'uuid'                              => $this->uuid,
            'fecha_inicio_escala'               => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino_escala'              => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'n_dias_40_solicitud'               => $this->n_dias_40 ? $this->n_dias_40  : null,
            'n_dias_100_solicitud'              => $this->n_dias_100 ? $this->n_dias_100 : null,
            'valor_dia_40_format_escala'        => $this->valor_dia_40 ? "$" . number_format($this->valor_dia_40, 0, ",", ".") : null,
            'valor_dia_100_format_escala'       => $this->valor_dia_100 ? "$" . number_format($this->valor_dia_100, 0, ",", ".") : null,
            'monto_40_format_calculo'           => $this->monto_40 ? "$" . number_format($this->monto_40, 0, ",", ".") : null,
            'monto_100_format_calculo'          => $this->monto_100 ? "$" . number_format($this->monto_100, 0, ",", ".") : null,
            'monto_total_format_calculo'        => $this->monto_total ? "$" . number_format($this->monto_total, 0, ",", ".") : null,
            'ley_escala'                        => $this->ley ? $this->ley->nombre : null,
            'grado_escala'                      => $this->grado ? $this->grado->nombre : null,
            'created_at'                        => $this->fecha_by_user ? Carbon::parse($this->fecha_by_user)->format('d-m-Y H:i:s') : null,
            'user_by'                           => $this->userBy ? $this->userBy->nombre_completo : null,
            'ajustes'                           => $this->ajustes ? ListAjustesCalculoResource::collection($this->ajustes) : null,
            'result'                            => $result
        ];
    }
}
