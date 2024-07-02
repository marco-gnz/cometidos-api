<?php

namespace App\Http\Controllers\User\Firmantes;

use App\Http\Controllers\Controller;
use App\Http\Resources\Firmante\ListFirmanteResource;
use App\Models\Contrato;
use App\Models\Grupo;
use Illuminate\Http\Request;

class FirmantesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function listFirmantes(Request $request)
    {
        $contrato       = Contrato::where('uuid', $request->contrato_uuid)->firstOrFail();
        $grupo_firma    = $contrato->grupo;

        $firmantes = [];
        if ($grupo_firma) {
            $firmantes = $grupo_firma->firmantes()->where('status', true)->get();
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
