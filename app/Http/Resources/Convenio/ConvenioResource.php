<?php

namespace App\Http\Resources\Convenio;

use App\Models\Convenio;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ConvenioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $funcionario = $this->funcionario ? "{$this->funcionario->nombre_completo} / {$this->funcionario->rut_completo}" : null;
        return [
            'uuid'                  => $this->uuid,
            'codigo'                => $this->codigo ? $this->codigo : null,
            'fecha_inicio'          => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'         => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'fecha_resolucion'      => $this->fecha_resolucion ? Carbon::parse($this->fecha_resolucion)->format('d-m-Y') : null,
            'tipo_convenio'         => Convenio::TYPE_NOM[$this->tipo_convenio],
            'active_value'          => $this->active ? true : false,
            'active_message'        => $this->active ? 'Habilitado' : 'Deshabilitado',
            'n_resolucion'          => $this->n_resolucion,
            'anio'                  => $this->anio,
            'n_viatico_mensual'     => $this->n_viatico_mensual,
            'observacion'           => $this->observacion ? $this->observacion : null,
            'estamento'             => $this->estamento ? $this->estamento->nombre : null,
            'ley'                   => $this->ley ? $this->ley->nombre : null,
            'establecimiento'       => $this->establecimiento ? $this->establecimiento->nombre : null,
            'ilustre'               => $this->ilustre ? $this->ilustre->nombre : null,
            'funcionario'           => $funcionario,
            'tipo_contrato'         => $this->tipo_contrato ? $this->tipo_contrato : null,
            'email'                 => $this->email ? $this->email : null,
            'user_by'               => $this->userBy ? $this->userBy->abreNombres() : null,
            'created_at'            => $this->created_at ? Carbon::parse($this->created_at)->format('d-m-Y H:i:s') : null,
            'solicitudes'           => $this->solicitudes_to_year,
            'years_to_solicitudes'  => $this->yearsToSolicitudes(),
            'filter_year'           => $this->filter_year
        ];
    }
}
