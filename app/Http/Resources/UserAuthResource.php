<?php

namespace App\Http\Resources;

use App\Http\Resources\User\Contrato\ListContratosResource;
use App\Http\Resources\User\CuentaBancariaResource;
use App\Models\Ausentismo;
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
        $is_firmante = false;
        $firmas = $this->firmas()->where('status', true)->where('role_id', '!=', 1)->count();
        $subrogancias = Ausentismo::whereHas('subrogantes', function ($q) {
            $q->where('users.id', $this->id);
        })->count();
        if ($firmas > 0 || $subrogancias > 0 || $this->hasRole('SUPER ADMINISTRADOR')) {
            $is_firmante = true;
        }

        return [
            'uuid'                      => $this->uuid,
            'id'                        => $this->id,
            'rut_completo'              => $this->rut_completo,
            'nombres'                   => $this->nombres,
            'nombre_completo'           => $this->nombre_completo,
            'nombre_abre'               => $this->abreNombres(),
            'email'                     => $this->email ? $this->email : null,
            'telefono'                  => $this->telefono ? $this->telefono : null,
            'create_solicitud'          => $this->authorizedToCreateSolicitud(),
            'create_rendicion'          => $this->authorizedToCreateRendicion(),
            'create_informe'            => $this->authorizedToCreateInforme(),
            'last_login'                => $this->lastHistory(HistoryActionUser::TYPE_2) ? Carbon::parse($this->lastHistory(HistoryActionUser::TYPE_2)->created_at)->format('d-m-Y H:i:s') : 'Sin registros',
            'last_change_password'      => $this->lastHistory(HistoryActionUser::TYPE_0) ? Carbon::parse($this->lastHistory(HistoryActionUser::TYPE_0)->created_at)->format('d-m-Y H:i:s') : 'Sin registros',
            'last_change_data'          => $this->lastHistory(HistoryActionUser::TYPE_1) ? Carbon::parse($this->lastHistory(HistoryActionUser::TYPE_1)->created_at)->format('d-m-Y H:i:s') : 'Sin registros',
            'last_change_request_data'  => $this->lastHistory(HistoryActionUser::TYPE_3) ? Carbon::parse($this->lastHistory(HistoryActionUser::TYPE_3)->created_at)->format('d-m-Y H:i:s') : 'Sin registros',
            'last_cuenta_bancaria'      => $this->lastCuentaBancaria() ? CuentaBancariaResource::make($this->lastCuentaBancaria())  : null,
            'is_firmante'               => true,
            'is_show_solicitud'         => true,
            'contratos'                 => $this->contratos ? ListContratosResource::collection($this->contratos) : [],
            'view_permisos'             => $this->viewPermisos()
        ];
    }
}
