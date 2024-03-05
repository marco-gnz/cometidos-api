<?php

namespace App\Http\Resources\User\Solicitud;

use App\Http\Resources\User\InformeCometido\ListInformeCometidoResource;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ListSolicitudResource extends JsonResource
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
            'fecha_inicio'              => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-y') : null,
            'fecha_termino'             => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-y') : null,
            'hora_llegada'              => $this->hora_llegada ? Carbon::parse($this->hora_llegada)->format('H:i') : null,
            'hora_salida'               => $this->hora_salida ? Carbon::parse($this->hora_salida)->format('H:i') : null,
            'establecimiento'           => $this->establecimiento ? $this->establecimiento->sigla : null,
            'derecho_pago'              => $this->derecho_pago ? "Si" : "No",
            'jornada'                   => Solicitud::JORNADA_ABRE[$this->jornada],
            'jornada_nom'               => Solicitud::JORNADA_NOM[$this->jornada],
            'tipo_comision'             => $this->tipoComision ? $this->tipoComision->nombre : null,
            'estado_nom'                => Solicitud::STATUS_NOM[$this->status],
            'type'                      => $this->typeStatus(),
            'type_last_status'          => $this->typeLastStatus(),
            'page_firma'                => $this->pageFirma(),
            'page_firma_porcentaje'     => $this->pageFirmaPorcentaje(),
            'created_at'                => Carbon::parse($this->created_at)->format('d-m-Y H:m'),
            'page_firma_ok'             => $this->pageFirmaIsOk(),
            'lugares'                   => $this->lugares()->pluck('nombre')->implode(', '),
            'fecha_value'               => [$this->fecha_inicio, $this->fecha_termino],
            'hora_value'                => [$this->hora_llegada, $this->hora_salida],
            'utiliza_transporte'        => $this->utiliza_transporte ? 1 : 0,
            'medio_transporte'          => $this->transportes ? $this->transportes->pluck('id')->toArray() : null,
            'actividad_realizada'       => $this->actividad_realizada,
            'is_informe'                => $this->informeCometido() ? true : false,
            'informe_cometido'          => $this->informeCometido() ? ListInformeCometidoResource::make($this->informeCometido()) : null,
            'is_update'                 => $this->isUpdate(),
            'is_informe_atrasado'       => $this->isInformeAtrasado(),
            'valor_total'               => $this->valorTotal()
        ];
    }
}
