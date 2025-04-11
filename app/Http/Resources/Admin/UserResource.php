<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\User\Contrato\ListContratosResource;
use App\Http\Resources\User\CuentaBancariaResource;
use App\Models\HistoryActionUser;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'uuid'              => $this->uuid,
            'rut_unique'        => $this->rut,
            'rut'               => $this->rut_completo,
            'nombres'           => $this->nombres,
            'apellidos'         => $this->apellidos,
            'email'             => $this->email ? $this->email : null,
            'status'            => $this->estado ? true : false,
            'nacionalidad'      => $this->nacionalidad ? $this->nacionalidad->nombre : null,
            'fecha_nacimiento'  => $this->fecha_nacimiento ? Carbon::parse($this->fecha_nacimiento)->format('d-m-Y') : null,

            'is_solicitud'      => $this->is_solicitud ? true : false,
            'is_informe'        => $this->is_informe ? true : false,
            'is_rendicion'      => $this->is_rendicion ? true : false,
            'is_subrogante'     => $this->is_subrogante ? true : false,

            'is_solicitud_message'      => $this->is_solicitud ? "Si" : "No",
            'is_informe_message'        => $this->is_informe ? "Si" : "No",
            'is_rendicion_message'      => $this->is_rendicion ? "Si" : "No",
            'is_subrogante_message'     => $this->is_subrogante ? "Si" : "No",
            'n_solicitudes'             => $this->solicitudes()->count(),
            'n_rendiciones'             => $this->procesoRendicionGastos()->count(),

            'establecimiento'   => $this->establecimiento ? $this->establecimiento->sigla : null,
            'estamento'         => $this->estamento ? $this->estamento->nombre : null,
            'cargo'             => $this->cargo ? $this->cargo->nombre : null,
            'depto'             => $this->departamento ? $this->departamento->nombre : null,
            'subdepto'          => $this->subDepartamento ? $this->subDepartamento->nombre : null,
            'hora'              => $this->hora ? $this->hora->nombre : null,
            'calidad'           => $this->calidad ? $this->calidad->nombre : null,
            'grado'             => $this->grado ? $this->grado->nombre : null,
            'ley'               => $this->ley ? $this->ley->nombre : null,
            'cuentas_bancarias' => $this->cuentas ? CuentaBancariaResource::collection($this->cuentas) : [],
            'contratos'         => $this->contratos ? ListContratosResource::collection($this->contratos) : [],
            'last_login'                => $this->lastHistory(HistoryActionUser::TYPE_2) ? Carbon::parse($this->lastHistory(HistoryActionUser::TYPE_2)->created_at)->format('d-m-Y H:i:s') : 'Sin registros',
            'last_change_password'      => $this->lastHistory(HistoryActionUser::TYPE_0) ? Carbon::parse($this->lastHistory(HistoryActionUser::TYPE_0)->created_at)->format('d-m-Y H:i:s') : 'Sin registros',
        ];
    }
}
