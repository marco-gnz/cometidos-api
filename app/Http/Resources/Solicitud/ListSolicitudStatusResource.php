<?php

namespace App\Http\Resources\Solicitud;

use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

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

        switch ($this->status) {
            case 1:
                $type = 'primary';
                break;

            case 2:
                $type = 'success';
                break;

            case 3:
                $type = 'danger';
                break;

            default:
                $type = 'info';
                break;
        }
        return [
            'status'            => $this->status,
            'status_nom'        => Solicitud::STATUS_NOM[$this->status],
            'observacion'       => $this->observacion ? $this->observacion : null,
            'posicion_firma'    => $this->posicion_firma,
            'history_solicitud' => $this->history_solicitud,
            'reasignacion'      => $this->reasignacion ? true : false,
            'reasignado'        => $this->reasignado ? true : false,
            'created_at'        => $this->created_at ? Carbon::parse($this->created_at)->format('d-m-Y H:i') : null,
            'type'              => $type,

            'funcionario_ingreso'       => $this->funcionario ? $this->funcionario->nombre_completo : null,
            'perfil_ingreso'            => $this->perfil ? $this->perfil->name : null,

            'funcionario_reasignado'       => $this->firmante ? $this->firmante->nombre_completo : null,
            'perfil_reasignado'             => $this->perfilFirmante ? $this->perfilFirmante->name : null,
        ];
    }
}
