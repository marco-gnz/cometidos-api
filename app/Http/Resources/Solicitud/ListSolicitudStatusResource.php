<?php

namespace App\Http\Resources\Solicitud;

use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class ListSolicitudStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        /* switch ($this->status) {
            case 1:
                $type = 'primary';
                break;

            case 2:
                $type = 'success';
                break;

            case 3:
            case 4:
                $type = 'danger';
                break;

            default:
                $type = 'info';
                break;
        }
        $octetos = explode('.', $this->ip_address);

        for ($i = count($octetos) - 2; $i < count($octetos); $i++) {
            $octetos[$i] = '***';
        }

        $ip_semi_crypt = implode('.', $octetos);

        $ejecucion_firma = null;
        if($this->funcionario){
            $ejecucion_firma = "Ejecutado por {$this->funcionario->nombre_completo} - {$this->perfil->name} ({$this->posicion_firma_s})";
        }

        $reasignado_firma = null;
        if($this->funcionarioRs){
            $reasignado_firma = "{$this->funcionarioRs->nombre_completo} - {$this->perfilRs->name} ({$this->posicion_firma_r_s})";
        } */

        $ejecucion_firma = null;
        if($this->funcionario){
            $ejecucion_firma = "Ejecutado por {$this->funcionario->nombre_completo} - {$this->perfil->name} ({$this->posicion_firma_s})";
        }

        $reasignado_firma = null;
        if($this->funcionarioRs){
            $reasignado_firma = "{$this->funcionarioRs->nombre_completo} - {$this->perfilRs->name} ({$this->posicion_firma_r_s})";
        }

        return [
            'status'                    => $this->status,
            'status_nom'                => EstadoSolicitud::STATUS_NOM[$this->status],
            'type'                      => $this->typeStatus(),
            'is_reasignado'             => $this->is_reasignado ? true : false,
            'motivo_rechazo_nom'        => $this->motivo_rechazo != null ? EstadoSolicitud::RECHAZO_NOM[$this->motivo_rechazo] : null,
            'observacion'               => $this->observacion ? $this->observacion : null,
            'ip_address'                => null,
            'created_at'                => $this->created_at ? Carbon::parse($this->created_at)->format('d-m-Y H:i') : null,
            'ejecucion'                 => $ejecucion_firma,
            'reasignado_firma'          => $reasignado_firma,
        ];
    }
}
