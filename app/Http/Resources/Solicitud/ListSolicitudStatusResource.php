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
        $email_ejecucion    = null;
        $ejecucion_firma    = null;
        $perfil_name        = $this->perfil ? "- {$this->perfil->name}" : '';
        $posicion           = $this->posicion_firma_s !== null ? "- $this->posicion_firma_s" : '';
        $is_subrogante      = $this->is_subrogante ? '(Subrogante)' : '';
        if ($this->funcionario) {
            $ejecucion_firma = "Ejecutado por {$this->firmaS->funcionario->nombre_completo} {$is_subrogante} {$perfil_name} {$posicion}";
            $email_ejecucion = $this->funcionario ? $this->funcionario->email : null;
        }

        $reasignado_firma = null;
        if ($this->funcionarioRs) {
            $reasignado_firma = "{$this->funcionarioRs->nombre_completo} {$is_subrogante} - {$this->perfilRs->name} ({$this->posicion_firma_r_s})";
            $email_ejecucion = $this->funcionarioRs ? $this->funcionarioRs->email : null;
        }

        return [
            'status'                    => $this->status,
            'status_nom'                => EstadoSolicitud::STATUS_NOM[$this->status],
            'type'                      => $this->typeStatus(),
            'is_reasignado'             => $this->is_reasignado ? true : false,
            'is_subrogante'             => $this->is_subrogante ? true : false,
            'motivo_rechazo_nom'        => $this->motivo_rechazo != null ? EstadoSolicitud::RECHAZO_NOM[$this->motivo_rechazo] : null,
            'observacion'               => $this->observacion ? $this->observacion : null,
            'ip_address'                => null,
            'created_at'                => $this->created_at ? Carbon::parse($this->created_at)->format('d-m-Y H:i:s') : null,
            'ejecucion'                 => $ejecucion_firma,
            'email_ejecucion'           => $email_ejecucion,
            'reasignado_firma'          => $reasignado_firma,
        ];
    }
}
