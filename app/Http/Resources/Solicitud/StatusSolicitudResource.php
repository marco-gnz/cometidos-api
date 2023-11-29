<?php

namespace App\Http\Resources\Solicitud;

use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusSolicitudResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $type = '#808080';
        if ($this->last_estado) {
            switch ($this->last_estado->status) {
                case 1:
                    $type = '#0e6db8';
                    break;
                case 0:
                case 2:
                    $type = '#67c23a';
                    break;

                case 3:
                    $type = '#dc3545';
                    break;
            }
        }
        return [
            'nombres'               => $this->funcionario->nombre_completo ? $this->funcionario->nombre_completo : null,
            'posicion_firma'        => $this->posicion_firma,
            'perfil'                => $this->perfil ? $this->perfil->name : null,
            'is_firma'              => $this->last_estado ? true : false,
            'status_nom'            => $this->last_estado ? Solicitud::STATUS_NOM[$this->last_estado->status] : null,
            'status_value'          => $this->last_estado  ? $this->last_estado->status : null,
            'status_date'           => $this->last_estado  ? Carbon::parse($this->last_estado->created_at)->format('d-m-Y H:i') : null,
            'type'                  => $type,
            'reasignacion'          => $this->reasignacion
        ];
    }
}
