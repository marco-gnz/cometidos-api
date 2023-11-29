<?php

namespace App\Http\Controllers\Admin\Solicitudes;

use App\Http\Controllers\Controller;
use App\Http\Resources\Solicitud\ListSolicitudAdminResource;
use App\Models\Solicitud;
use Illuminate\Http\Request;

class SolicitudController extends Controller
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
                    'data'          => ListSolicitudAdminResource::collection($solicitudes)
                )
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function findSolicitud($uuid)
    {
        try {
            $solicitud = Solicitud::where('uuid', $uuid)->firstOrFail();

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => ListSolicitudAdminResource::make($solicitud)
                )
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }
}
