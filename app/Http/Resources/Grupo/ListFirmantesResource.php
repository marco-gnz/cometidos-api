<?php

namespace App\Http\Resources\Grupo;

use App\Models\Ausentismo;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ListFirmantesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $solicitud_created      = Carbon::parse($this->solicitud->fecha_by_user)->format('Y-m-d');
        $total_ausentismos_hoy  = $this->funcionario->ausentismos()
            ->where(function ($q) use ($solicitud_created) {
                $q->where('fecha_inicio', '>=', $solicitud_created)
                    ->where('fecha_termino', '<=', $solicitud_created);
            })
            ->first();

        $reasignacion = $this->funcionario->reasignacionAusencias()
            ->whereHas('solicitudes')
            ->first();

        return [
            'uuid'                  => $this->uuid,
            'rut'                   => $this->funcionario->rut ? $this->funcionario->rut : null,
            'nombres'               => $this->funcionario->nombre_completo ? $this->funcionario->nombre_completo : null,
            'email'                 => $this->funcionario->email ? $this->funcionario->email : null,
            'posicion_firma'        => $this->posicion_firma,
            'perfil'                => $this->perfil ? $this->perfil->name : null,
            'status'                => $this->status ? true : false,
            'total_ausentismos_hoy' => $total_ausentismos_hoy ? 'Si' : 'No',
            'reasignacion'          => $reasignacion ? 'Si' : 'No',
            'is_reasignado'         => $this->is_reasignado,
            'is_firma'              => $this->solicitud ? ($this->posicion_firma === $this->solicitud->posicion_firma_actual ? true : false) : false,
            'authorized_to_update'  => $this->authorizedToUpdate(),
            'is_executed'           => $this->is_executed,
            'is_success'            => $this->is_success
        ];
    }
}
