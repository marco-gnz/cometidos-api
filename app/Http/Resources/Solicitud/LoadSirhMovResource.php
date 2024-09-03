<?php

namespace App\Http\Resources\Solicitud;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class LoadSirhMovResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user_by    = $this->userBy ? $this->userBy->abreNombres() : null;
        $created_at = Carbon::parse($this->created_at)->format('d-m-Y H:i:s');
        return [
            'user_by'       => $user_by,
            'created_at'    => $created_at,
            'concat'        => "$user_by $created_at"
        ];
    }
}
