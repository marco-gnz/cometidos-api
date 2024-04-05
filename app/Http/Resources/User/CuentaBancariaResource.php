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
        return [
            'uuid'          => $this->uuid,
            'status'        => $this->status ? true : false,
            'status_name'   => $this->status ? 'Habilitada' : 'No habilitada',
            'n_cuenta'      => $this->n_cuenta ? $this->n_cuenta : null,
            'tipo_cuenta'   => CuentaBancaria::TYPE_ACCOUNT_NOM[$this->tipo_cuenta],
            'banco'         => $this->banco ? $this->banco->nombre : null,
            'created_at'    => Carbon::parse($this->fecha_by_user)->format('d-m-Y H:i:s')
        ];
    }
}
