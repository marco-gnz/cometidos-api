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
        $lugaresCollection  = $this->whenLoaded('lugares');
        $paisesCollection   = $this->whenLoaded('lugares');
        $motivosCollection  = $this->whenLoaded('motivos');

        $lugares        = null;
        $lugares_abre   = null;
        $other_lugares  = false;
        $motivos        = null;
        $motivos_abre   = null;
        $other_motivos  = false;

        if ($lugaresCollection->isNotEmpty()) {
            $lugares        = $lugaresCollection->first()->nombre;
            $lugares_abre   = mb_substr($lugares, 0, 15, 'UTF-8');
            $other_lugares  = $lugaresCollection->count() > 1;
        } elseif ($paisesCollection->isNotEmpty()) {
            $lugares        = $paisesCollection->first()->nombre;
            $other_lugares  = $paisesCollection->count() > 1;
        }

        if ($motivosCollection->isNotEmpty()) {
            $motivos        = $motivosCollection->first()->nombre;
            $motivos_abre   = mb_substr($motivos, 0, 15, 'UTF-8');
            $other_motivos  = $motivosCollection->count() > 1;
        }

        $authorized_to_firma = false;
        if (!$this->derecho_pago) {
            $authorized_to_firma = $this->authorizedToFirma();
        }
        $departamento_nom_completo = $this->whenLoaded('departamento', function () {
            return $this->departamento->nombre;
        });
        $departamento_nom_abre = substr($departamento_nom_completo, 0, 15);
        return [
            'uuid'                      => $this->uuid,
            'codigo'                    => $this->codigo,
            'fecha_inicio'              => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-y') : null,
            'fecha_termino'             => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-y') : null,
            'hora_llegada'              => $this->hora_llegada ? Carbon::parse($this->hora_llegada)->format('H:i') : null,
            'hora_salida'               => $this->hora_salida ? Carbon::parse($this->hora_salida)->format('H:i') : null,
            'funcionario'               => $this->whenLoaded('funcionario', function () {
                return $this->funcionario->abreNombresList();
            }),
            'departamento'               => $departamento_nom_completo,
            'departamento_abre'          => $departamento_nom_abre,
            'establecimiento'            => $this->whenLoaded('establecimiento', function () {
                return $this->establecimiento->sigla;
            }),
            'derecho_pago_value'        => $this->derecho_pago ? true : false,
            'derecho_pago'              => $this->derecho_pago ? "Si" : "No",
            'estado_nom'                => Solicitud::STATUS_NOM[$this->status],
            'estado_type'               => $this->typeStatus(),
            'page_firma_desc'           => $this->pageFirmaDesc(),
            'is_grupo'                  => $this->whenLoaded('grupo', function () {
                return $this->isGrupo();
            }),
            'informe_cometido' => $this->whenLoaded('ultimoInformeCometido', function () {
                return InformeCometidoResource::make($this->ultimoInformeCometido);
            }),
            'jefatura_directa' => $this->whenLoaded('jefaturaDirectaRelation', function () {
                return $this->jefaturaDirectaRelation->funcionario
                    ? $this->jefaturaDirectaRelation->funcionario->abreNombresList()
                    : null;
            }),
            'created_at'    => Carbon::parse($this->created_at)->format('d-m-y H:i'),
            'monto_total'   => $this->derecho_pago
                ? ($this->lastCalculo ? $this->lastCalculo->valorizacionTotalPagar()->monto_total_pagar : 'S/V')
                : 'N/A',
            'monto_total_is_bold'   => $this->derecho_pago ? ($this->lastCalculo ? true : false) : false,
            'lugares'               => $lugares,
            'lugares_abre'          => $lugares_abre,
            'other_lugares'         => $other_lugares,
            'motivos'               => $motivos,
            'motivos_abre'          => $motivos_abre,
            'other_motivos'         => $other_motivos,
            'authorized_to_firma'   => $authorized_to_firma,
        ];
    }
}
