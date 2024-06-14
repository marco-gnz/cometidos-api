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
        $contrato   = Contrato::where('uuid', $request->contrato_uuid)->firstOrFail();
        $grupo      = Grupo::where('establecimiento_id', $contrato->establecimiento_id)
            ->where('departamento_id', $contrato->departamento_id)
            ->where('sub_departamento_id', $contrato->sub_departamento_id)
            ->whereDoesntHave('firmantes', function ($q) use($contrato) {
                $q->where('user_id', $contrato->user_id);
            })
            ->first();

        $firmantes = [];
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
