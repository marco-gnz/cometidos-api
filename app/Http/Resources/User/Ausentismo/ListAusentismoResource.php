<?php

namespace App\Http\Resources\User\Ausentismo;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ListAusentismoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        if ($this->subrogantes) {
            $nombres = $this->subrogantes->map(function ($subrogante) {
                return "{$subrogante->abreNombres()} {$subrogante->email}";
            })->toArray();

            $resultado = implode(', ', $nombres);
        } else {
            $resultado = null;
        }


        $resultado = implode(', ', $nombres);
        return [
            'uuid'              => $this->uuid,
            'nombres_firmante_ausente'  => $this->firmanteAusente ? $this->firmanteAusente->abreNombres() : null,
            'fecha_inicio'      => $this->fecha_inicio ? Carbon::parse($this->fecha_inicio)->format('d-m-Y') : null,
            'fecha_termino'     => $this->fecha_termino ? Carbon::parse($this->fecha_termino)->format('d-m-Y') : null,
            'subrogantes'       => $resultado
        ];
    }
}
