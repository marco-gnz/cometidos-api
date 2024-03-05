<?php

namespace App\Http\Resources\Solicitud;

use App\Models\EstadoSolicitud;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Resources\Json\JsonResource;

class ListSolicitudAdminResource extends JsonResource
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
            'uuid'                      => $this->uuid,
            'codigo'                    => $this->codigo,
            'fecha_inicio'              => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'             => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'funcionario'               => $this->funcionario ? Str::limit(strip_tags($this->funcionario->nombre_completo), 20) : null,
            'departamento'              => $this->departamento ? substr($this->departamento->nombre, 0, 15) : null,
            'subdepartamento'           => $this->subdepartamento ? substr($this->subdepartamento->nombre, 0, 15) : null,
            'departamento_complete'     => $this->departamento ? $this->departamento->nombre : null,
            'subdepartamento_complete'  => $this->subdepartamento ? $this->subdepartamento->nombre : null,
            'establecimiento'           => $this->establecimiento ? $this->establecimiento->sigla : null,
            'derecho_pago_value'        => $this->derecho_pago ? true : false,
            'derecho_pago'              => $this->derecho_pago ? "Si" : "No",
            'estado_nom'                => EstadoSolicitud::STATUS_NOM[$this->last_status],
            'tipo_comision'             => $this->tipoComision ? $this->tipoComision->nombre : null,
            'dentro_pais'               => $this->dentro_pais ? true : false,
            'afecta_convenio'           => $this->afecta_convenio !== null ? ($this->afecta_convenio === 1 ? 'AFECTA' : 'NO AFECTA') : null,
            'page_firma'                => $this->pageFirma()
        ];
    }
}
