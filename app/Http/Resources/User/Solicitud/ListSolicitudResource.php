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
        $informe = (object) [
            'is_store'                  => $this->authorizedToCreateInformeCometido(),
            'data'                      => $this->informeCometido() ? ListInformeCometidoResource::make($this->informeCometido()) : null,
            'is_informe_atrasado'       => $this->isInformeAtrasado()
        ];

        return [
            'uuid'                      => $this->uuid,
            'codigo'                    => $this->codigo,
            'fecha_inicio'              => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'             => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'hora_llegada'              => $this->hora_llegada ? Carbon::parse($this->hora_llegada)->format('H:i') : null,
            'hora_salida'               => $this->hora_salida ? Carbon::parse($this->hora_salida)->format('H:i') : null,
            'derecho_pago_value'        => $this->derecho_pago ? true : false,
            'derecho_pago'              => $this->derecho_pago ? "Si" : "No",
            'n_dias_40'                 => $this->n_dias_40,
            'n_dias_100'                => $this->n_dias_100,
            'jornada'                   => Solicitud::JORNADA_ABRE[$this->jornada],
            'jornada_nom'               => Solicitud::JORNADA_NOM[$this->jornada],
            'estado_nom'                => Solicitud::STATUS_NOM[$this->status],
            'type'                      => $this->typeStatus(),
            'type_last_status'          => $this->typeLastStatus(),
            'page_firma'                => $this->pageFirma(),
            'type_page_firma'           => $this->typePageFirma(),
            'created_at'                => Carbon::parse($this->created_at)->format('d-m-Y H:i'),
            'page_firma_ok'             => $this->pageFirmaIsOk(),
            'informe'                   => $informe,
            'is_update'                 => $this->authorizedToUpdate(),
            'valor_total'               => $this->valorTotal(),
            'not_actividad'             => $this->isNotActividad(),
            'authorized_to_anular'      => $this->authorizedToAnular(),
            'lugares'                   => $this->lugares ? $this->lugares->first()->nombre : null,
            'other_lugares'             => $this->lugares()->count() > 1 ? true : false
        ];
    }
}
