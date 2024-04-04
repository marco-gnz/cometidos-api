<?php

namespace App\Http\Resources\Rendicion;

use App\Models\RendicionGasto;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RendicionGastoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        switch ($this->last_status) {
            case 0:
                $type = 'info';
                break;

            case 1:
                $type = 'success';
                break;

            case 2:
                $type = 'danger';
                break;
        }
        $last_status = $this->estados ? $this->estados()->orderBy('id', 'DESC')->first() : null;
        return [
            'uuid'                              => $this->uuid,
            'nombre'                            => $this->actividad ? $this->actividad->nombre : null,
            'is_particular'                     => $this->actividad ? ($this->actividad->is_particular ? true : false) : null,
            'rinde_gasto_value'                 => $this->rinde_gasto ? true : false,
            'rinde_gasto'                       => $this->rinde_gasto ? 'Si' : 'No',
            'mount'                             => $this->mount ? $this->mount : null,
            'mount_format'                      => $this->mount ? "$" . number_format($this->mount, 0, ",", ".") : null,
            'mount_real'                        => $this->mount_real ? $this->mount_real : null,
            'mount_real_format'                 => $this->mount_real && $this->last_status != 2 ? "$" . number_format($this->mount_real, 0, ",", ".") : null,
            'rinde_gastos_servicio'             => $this->rinde_gastos_servicio ? true : false,
            'status_value'                      => $this->last_status,
            'status_type'                       => $type,
            'status_nom'                        => RendicionGasto::STATUS_NOM[$this->last_status],
            'last_status'                       => $last_status ? StatusRendicionResource::make($last_status) : null,
            'item_presupuestario'               => $this->itemPresupuestario ? $this->itemPresupuestario->nombre : null,
            'authorized_to_update'              => $this->authorizedToUpdate(),
            'authorized_to_update_mount'        => $this->authorizedToUpdateMount(),
            'authorized_to_aprobar'             => $this->authorizedToAprobar(),
            'authorized_to_rechazar'            => $this->authorizedToRechazar(),
            'authorized_to_resetear'            => $this->authorizedToResetear(),
        ];
    }
}
