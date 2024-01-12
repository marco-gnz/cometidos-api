<?php

namespace App\Http\Resources\Rendicion;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcesoRendicionGastoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $rendiciones_sum_soli   = $this->rendiciones->where('rinde_gasto', true)->sum('mount');
        $rendiciones_sum        = $this->rendiciones->where('rinde_gasto', true)->where('last_status', 1)->sum('mount_real');
        return [
            'uuid'                              => $this->uuid,
            'n_rendicion'                       => $this->n_rendicion,
            'solicitud_codigo'                  => $this->solicitud ? $this->solicitud->codigo : null,
            'solicitud_fecha_inicio'            => $this->solicitud ? Carbon::parse($this->solicitud->fecha_inicio)->format('d-m-Y'): null,
            'solicitud_fecha_termino'           => $this->solicitud ? Carbon::parse($this->solicitud->fecha_termino)->format('d-m-Y') : null,
            'funcionario'                       => $this->solicitud ? $this->solicitud->funcionario->nombre_completo : null,
            'establecimiento'                   => $this->solicitud ? $this->solicitud->establecimiento->sigla : null,
            'rendiciones_count'                 => $this->rendiciones->where('rinde_gasto', true)->where('last_status', 1)->count(),
            'rendiciones_count_pendiente'       => $this->rendiciones->where('rinde_gasto', true)->where('last_status', 0)->count(),
            'rendiciones_sum_soli'              => $rendiciones_sum_soli,
            'rendiciones_sum_format_soli'       => "$".number_format($rendiciones_sum_soli, 0, ",", "."),
            'rendiciones_sum'                   => $rendiciones_sum,
            'rendiciones_sum_format'            => "$".number_format($rendiciones_sum, 0, ",", "."),
        ];
    }
}
