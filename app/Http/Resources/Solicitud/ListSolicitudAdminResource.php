<?php

namespace App\Http\Resources\Solicitud;

use App\Http\Resources\User\InformeCometido\ListInformeCometidoResource;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
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
            'fijada'                    => $this->isPinnedByUser(auth()->user()),
            'fecha_inicio'              => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-y') : null,
            'fecha_termino'             => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-y') : null,
            'funcionario'               => $this->funcionario ? $this->funcionario->abreNombresList() : null,
            'departamento'              => $this->departamento ? substr($this->departamento->nombre, 0, 15) : null,
            'departamento_complete'     => $this->departamento ? $this->departamento->nombre : null,
            'establecimiento'           => $this->establecimiento ? $this->establecimiento->sigla : null,
            'derecho_pago_value'        => $this->derecho_pago ? true : false,
            'derecho_pago'              => $this->derecho_pago ? "Si" : "No",
            'estado_nom'                => Solicitud::STATUS_NOM[$this->status],
            'estado_type'               => $this->typeStatus(),
            'page_firma'                => $this->pageFirma(),
            'type_page_firma'           => $this->typePageFirma(),
            'is_grupo'                  => $this->isGrupo(),
            'informe_cometido'          => $this->informeCometido() ? ListInformeCometidoResource::make($this->informeCometido()) : null,
            'firma_pendiente'           => $this->isFirmaPendiente() ? $this->isFirmaPendiente()->funcionario->abreNombres() : null,
            'jefatura_directa'          => $this->jefaturaDirecta() ? $this->jefaturaDirecta()->funcionario->abreNombres()  : null,
            'created_at'                => Carbon::parse($this->created_at)->format('d-m-y H:i')
        ];
    }
}
