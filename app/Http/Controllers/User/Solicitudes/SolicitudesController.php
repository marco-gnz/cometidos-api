<?php

namespace App\Http\Controllers\User\Solicitudes;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\Solicitud\ListSolicitudResource;
use App\Models\Solicitud;
use Illuminate\Http\Request;

class SolicitudesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function listSolicitudes()
    {
        try {
            $solicitudes = Solicitud::orderBy('fecha_inicio', 'DESC')->get();

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => ListSolicitudResource::collection($solicitudes)
                )
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }
}
