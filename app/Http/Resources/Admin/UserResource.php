<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\User\Contrato\ListContratosResource;
use App\Http\Resources\User\CuentaBancariaResource;
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

            'is_solicitud'      => $this->is_solicitud ? true : false,
            'is_informe'        => $this->is_informe ? true : false,
            'is_rendicion'      => $this->is_rendicion ? true : false,
            'is_subrogante'     => $this->is_subrogante ? true : false,

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
            'total_viaticos_procesados'     => $this->totalViaticosProcesados(),
            'total_valorizacion'            => $this->totalValorizacion(),
            'total_rendiciones'              => $this->totalRendiciones()
        ];
    }
}
