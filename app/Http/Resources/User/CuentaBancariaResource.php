<?php

namespace App\Http\Resources\User;

use App\Models\CuentaBancaria;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class CuentaBancariaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $status         = $this->status ? 'Habilitada en sistema' : 'No habilitada en sistema';
        $n_cuenta       = $this->n_cuenta ? "N° {$this->n_cuenta}": "";
        $tipo_cuenta    = $this->tipo_cuenta !== null ? CuentaBancaria::TYPE_ACCOUNT_NOM[$this->tipo_cuenta] : 'SIN_TIPO_DE_CUENTA';
        $banco          = $this->banco ? $this->banco->nombre : "";
        $descripcion    = "{$n_cuenta} {$tipo_cuenta} {$banco} - {$status}";
        return [
            'uuid'          => $this->uuid,
            'status'        => $this->status ? true : false,
            'status_name'   => $status,
            'n_cuenta'      => $this->n_cuenta ? $this->n_cuenta : null,
            'tipo_cuenta'   => $tipo_cuenta,
            'banco'         => $this->banco ? $this->banco->nombre : null,
            'created_at'    => Carbon::parse($this->fecha_by_user)->format('d-m-Y H:i:s'),
            'descripcion'   => $descripcion
        ];
    }
}
