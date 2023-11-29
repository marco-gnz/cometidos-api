<?php

namespace App\Http\Resources\Grupo;

use App\Models\Ausentismo;
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
        $total_ausentismos_hoy = Ausentismo::where('user_ausente_id', $this->funcionario->id)->first();

        return [
            'nombres'               => $this->funcionario->nombre_completo ? $this->funcionario->nombre_completo : null,
            'email'                 => $this->funcionario->email ? $this->funcionario->email : null,
            'posicion_firma'        => $this->posicion_firma,
            'perfil'                => $this->perfil ? $this->perfil->name : null,
            'total_ausentismos_hoy' => $total_ausentismos_hoy ? 'Si' : 'No'
        ];
    }
}
