<?php

namespace App\Http\Controllers\User\Firmantes;

use App\Http\Controllers\Controller;
use App\Http\Resources\Firmante\ListFirmanteResource;
use App\Models\Grupo;
use Illuminate\Http\Request;

class FirmantesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function listFirmantes()
    {
        $user_auth = auth()->user();
        $grupo = Grupo::where('establecimiento_id', $user_auth->establecimiento_id)
            ->where('departamento_id', $user_auth->departamento_id)
            ->where('sub_departamento_id', $user_auth->sub_departamento_id)
            ->first();

        if ($grupo) {
            $firmantes = $grupo->firmantes()->where('status', true)->get();
        }

        return response()->json(
            array(
                'status'        => 'success',
                'title'         => null,
                'message'       => null,
                'data'          => ListFirmanteResource::collection($firmantes),
            )
        );
    }
}
