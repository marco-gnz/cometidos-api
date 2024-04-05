<?php

namespace App\Http\Resources;

use App\Http\Resources\User\CuentaBancariaResource;
use App\Models\Grupo;
use App\Models\HistoryActionUser;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAuthResource extends JsonResource
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
            'uuid'                      => $this->uuid,
            'id'                        => $this->id,
            'rut_completo'              => $this->rut_completo,
            'nombres'                   => $this->nombres,
            'nombre_completo'           => $this->nombre_completo,
            'nombre_abre'               => $this->abreNombres(),
            'email'                     => $this->email ? $this->email : null,
            'ley'                       => $this->ley ? $this->ley->nombre : null,
            'grado'                     => $this->grado ? $this->grado->nombre : 'Sin grado',
            'cargo'                     => $this->cargo ? $this->cargo->nombre : null,
            'departamento'              => $this->departamento ? $this->departamento->nombre : null,
            'sub_departamento'          => $this->subDepartamento ? $this->subDepartamento->nombre : null,
            'establecimiento'           => $this->establecimiento ? $this->establecimiento->nombre : null,
            'estamento'                 => $this->estamento ? $this->estamento->nombre : null,
            'hora'                      => $this->hora ? $this->hora->nombre : null,
            'calidad'                   => $this->calidad ? $this->calidad->nombre : null,
            'telefono'                  => $this->telefono ? $this->telefono : null,
            'is_group'                  => Grupo::where('departamento_id', $this->departamento->id)->where('sub_departamento_id', $this->subDepartamento->id)->where('establecimiento_id', $this->establecimiento->id)->first() ? true : false,
            'create_solicitud'          => $this->authorizedToCreateSolicitud(),
            'last_login'                => $this->lastHistory(HistoryActionUser::TYPE_2) ? Carbon::parse($this->lastHistory(HistoryActionUser::TYPE_2)->created_at)->format('d-m-Y H:i:s') : 'Sin registros',
            'last_change_password'      => $this->lastHistory(HistoryActionUser::TYPE_0) ? Carbon::parse($this->lastHistory(HistoryActionUser::TYPE_0)->created_at)->format('d-m-Y H:i:s') : 'Sin registros',
            'last_change_data'          => $this->lastHistory(HistoryActionUser::TYPE_1) ? Carbon::parse($this->lastHistory(HistoryActionUser::TYPE_1)->created_at)->format('d-m-Y H:i:s') : 'Sin registros',
            'last_change_request_data'  => $this->lastHistory(HistoryActionUser::TYPE_3) ? Carbon::parse($this->lastHistory(HistoryActionUser::TYPE_3)->created_at)->format('d-m-Y H:i:s') : 'Sin registros',
            'last_cuenta_bancaria'      => $this->lastCuentaBancaria() ? CuentaBancariaResource::make($this->lastCuentaBancaria())  : null
        ];
    }
}
