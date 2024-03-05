<?php

namespace App\Http\Resources\Rendicion;

use App\Http\Resources\Solicitud\ListSolicitudDocumentosResource;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcesoRendicionGastoDetalleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $particularCondition = function ($q) {
            $q->where('is_particular', true);
        };

        $notParticularCondition = function ($q) {
            $q->where('is_particular', false);
        };

        $getRendiciones = function ($condition) {
            return $this->rendiciones ? $this->rendiciones()->whereHas('actividad', $condition)->get() : null;
        };

        $getSum = function ($condition, $mount) {
            return $this->rendiciones()->whereHas('actividad', $condition)
                ->where(function ($q) use ($mount) {
                    if ($mount === 'mount_real') {
                        return $q->where('last_status', 1);
                    }
                    return $q;
                })
                ->where('rinde_gasto', true)->sum($mount);
        };

        $mount      = 'mount';
        $mountReal  = 'mount_real';

        return [
            'uuid'                                          => $this->uuid,
            'n_folio'                                       => $this->n_folio,
            'n_folio'                                       => $this->n_folio,
            'rut_funcionario'                               => optional($this->solicitud->funcionario)->rut_completo,
            'nombres_funcionario'                           => optional($this->solicitud->funcionario)->nombre_completo,
            'correo_funcionario'                            => optional($this->solicitud->funcionario)->email,
            'establecimiento'                               => optional($this->solicitud->establecimiento)->nombre,
            'departamento'                                  => optional($this->solicitud->departamento)->nombre,
            'subdepartamento'                               => optional($this->solicitud->subdepartamento)->nombre,
            'dentro_pais'                                   => $this->solicitud->dentro_pais ?? false,
            'lugares'                                       => optional($this->solicitud->lugares)->pluck('nombre')->implode(', '),
            'paises'                                        => optional($this->solicitud->paises)->pluck('nombre')->implode(', '),
            'motivos'                                       => optional($this->solicitud->motivos)->pluck('nombre')->implode(', '),
            'n_rendicion'                                   => $this->n_rendicion,
            'solicitud_codigo'                              => optional($this->solicitud)->codigo,
            'solicitud_fecha_inicio'                        => optional($this->solicitud)->fecha_inicio ? Carbon::parse($this->solicitud->fecha_inicio)->format('d-m-Y') : null,
            'solicitud_fecha_termino'                       => optional($this->solicitud)->fecha_termino ? Carbon::parse($this->solicitud->fecha_termino)->format('d-m-Y') : null,
            'solicitud_transporte'                          => optional($this->solicitud->transportes)->pluck('nombre')->implode(', '),
            'rendiciones_count'                             => $this->rendiciones->where('rinde_gasto', true)->where('last_status', 1)->count(),
            'rendiciones_sum_particular'                    => $getSum($particularCondition, $mount),
            'rendiciones_sum_particular_format'             => "$" . number_format($getSum($particularCondition, $mount), 0, ",", "."),
            'rendiciones_sum_real_particular'               => $getSum($particularCondition, $mountReal),
            'rendiciones_sum_real_particular_format'        => "$" . number_format($getSum($particularCondition, $mountReal), 0, ",", "."),
            'rendiciones_sum_not_particular'                => $getSum($notParticularCondition, $mount),
            'rendiciones_sum_not_particular_format'         => "$" . number_format($getSum($notParticularCondition, $mount), 0, ",", "."),
            'rendiciones_sum_real_not_particular'           => $getSum($notParticularCondition, $mountReal),
            'rendiciones_sum_real_not_particular_format'    => "$" . number_format($getSum($notParticularCondition, $mountReal), 0, ",", "."),
            'rendiciones_particular'                        => $getRendiciones($particularCondition) ? RendicionGastoResource::collection($getRendiciones($particularCondition)) : null,
            'rendiciones_not_particular'                    => $getRendiciones($notParticularCondition) ? RendicionGastoResource::collection($getRendiciones($notParticularCondition)) : null,
            'documentos'                                    => $this->documentos && count($this->documentos) > 0 ? ListSolicitudDocumentosResource::collection($this->documentos) : null,
            'created_at'                                    => $this->created_at ? Carbon::parse($this->created_at)->format('d-m-Y H:i') : null,
            'url_gastos_cometido_funcional'                 => $this->uuid ? route('gastoscometidofuncional.show', ['uuid' => $this->uuid]) : null
        ];
    }
}
