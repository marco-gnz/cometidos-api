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
        return parent::toArray($request);
        /* return [
            'posicion_firma_actual_solicitud'   => $this->posicion_firma_actual_solicitud,
            'is_ciclo'                          => $this->is_ciclo,
            'user_uuid'             => $this->user_uuid,
            'nombres_firmante'      => $this->nombres_firmante,
            'posicion_firma'        => $this->posicion_firma,
            'perfil'                => $this->perfil,
            'status_nom'            => $this->status_nom ? $this->status_nom : null,
            'status_value'          => $this->status_value,
            'status_date'           => $this->status_date ? $this->status_date : null,
            'status_firmante'       => $this->status_firmante,
            'is_reasignado'         => $this->is_reasignado,
            'reasignar_firma_value' => $this->reasignar_firma_value,
            'type'                  => $this->type,
            'is_actual'             => $this->is_actual,
            'is_firma'              => $this->is_firma
        ]; */
    }
}
