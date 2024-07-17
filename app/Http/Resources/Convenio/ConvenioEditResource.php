<?php

namespace App\Http\Resources\Convenio;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ConvenioEditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $tz         = 'America/Santiago';
        $anio       = Carbon::createFromDate($this->anio, '01', '01', $tz);
        return [
            'codigo'                => $this->codigo ? $this->codigo : null,
            'uuid'                  => $this->uuid,
            'periodo'               => [$this->fecha_inicio, $this->fecha_termino],
            'fecha_resolucion'      => $this->fecha_resolucion,
            'n_resolucion'          => $this->n_resolucion,
            'n_viatico_mensual'     => $this->n_viatico_mensual,
            'anio'                  => $anio->format('Y-m-d'),
            'observacion'           => $this->observacion,
            'estamento_id'          => $this->estamento_id,
            'ley_id'                => $this->ley_id,
            'establecimiento_id'    => $this->establecimiento_id,
            'ilustre_id'            => $this->ilustre_id,
            'funcionario'           => $this->funcionario ? $this->funcionario->nombre_completo : null,
            'tipo_contrato'         => $this->tipo_contrato ? $this->tipo_contrato : null,
            'email'                 => $this->email
        ];
    }
}
