<?php

namespace App\Http\Resources\Solicitud;

use Illuminate\Http\Resources\Json\JsonResource;

class UpdateSolicitudResource extends JsonResource
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
            'codigo'                            => $this->codigo,
            'user_id'                           => $this->funcionario->id,
            'fecha_inicio'                      => $this->fecha_inicio,
            'fecha_termino'                     => $this->fecha_termino,
            'fecha_solicitud'                   => [$this->fecha_inicio, $this->fecha_termino],
            'hora_llegada'                      => $this->hora_llegada,
            'hora_salida'                       => $this->hora_salida,
            'hora_solicitud'                    => [$this->hora_llegada, $this->hora_salida],
            'derecho_pago'                      => $this->derecho_pago ? 1 : 0,
            'alimentacion_red'                  => $this->alimentacion_red ? 1 : 0,
            'utiliza_transporte'                => $this->utiliza_transporte ? 1 : 0,
            'viaja_acompaniante'                => $this->viaja_acompaniante ? 1 : 0,
            'actividad_realizada'               => $this->actividad_realizada,
            'gastos_alimentacion'               => $this->gastos_alimentacion ? 1 : 0,
            'gastos_alojamiento'                => $this->gastos_alojamiento ? 1 : 0,
            'pernocta_lugar_residencia'         => $this->pernocta_lugar_residencia ? 1 : 0,
            'n_dias_40'                         => $this->n_dias_40,
            'n_dias_100'                        => $this->n_dias_100,
            'observacion_gastos'                => $this->observacion_gastos,
            'motivos_cometido'                  => $this->motivos ? $this->motivos->pluck('id') : [],
            'lugares_cometido'                  => $this->lugares ? $this->lugares->pluck('id') : [],
            'medio_transporte'                  => $this->transportes ? $this->transportes->pluck('id') : [],
            'tipo_comision_id'                  => $this->tipo_comision_id ? $this->tipo_comision_id : null,
            'jornada'                           => $this->jornada,
            'paises_cometido'                   => $this->paises ? $this->paises->pluck('id') : [],
            'dentro_pais'                       => $this->dentro_pais ? 1 : 0,
            'archivos'                          => [],
            'is_update'                         => $this->authorizedToUpdate(),
            'is_store_informe_cometido'         => $this->authorizedToCreateInformeCometido(),
            'dias_permitidos'                   => $this->dias_permitidos
        ];
    }
}
