<?php

namespace App\Http\Controllers\User\Solicitudes;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListSolicitudCalculoAdminResource;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoDetalleResource;
use App\Http\Resources\Solicitud\ListCalculoResoruce;
use App\Http\Resources\Solicitud\ListConvenioResource;
use App\Http\Resources\Solicitud\ListInformeCometidoAdminResource;
use App\Http\Resources\Solicitud\ListSolicitudDocumentosResource;
use App\Http\Resources\Solicitud\ListSolicitudStatusResource;
use App\Http\Resources\User\Solicitud\DatosSolicitudResource;
use App\Http\Resources\User\Solicitud\ListSolicitudResource;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use App\Traits\StatusSolicitudTrait;
use Illuminate\Support\Facades\Auth;

class SolicitudesController extends Controller
{
    use StatusSolicitudTrait;

    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function listSolicitudes()
    {
        try {
            $auth = Auth::user();
            $solicitudes = Solicitud::where('user_id', $auth->id)->orderBy('fecha_inicio', 'DESC')->get();

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

    public function getSolicitud($uuid, $nav)
    {
        try {
            $solicitud = Solicitud::where('uuid', $uuid)->withCount('documentos')->firstOrFail();

            switch ($nav) {
                case 'datos':
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => DatosSolicitudResource::make($solicitud),
                            'nav'           => $this->navStatusSolicitud($solicitud)
                        )
                    );
                    break;

                case 'informes':
                    $informes = $solicitud->informes()->orderBy('id', 'DESC')->get();

                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => DatosSolicitudResource::make($solicitud),
                            'informes'      => ListInformeCometidoAdminResource::collection($informes),
                            'nav'           => $this->navStatusSolicitud($solicitud)
                        )
                    );
                    break;

                case 'calculo':
                    $calculo = $solicitud->getLastCalculo();

                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => DatosSolicitudResource::make($solicitud),
                            'calculo'       => $calculo ? ListCalculoResoruce::make($calculo) : null,
                            'nav'           => $this->navStatusSolicitud($solicitud)
                        )
                    );
                    break;

                case 'rendiciones':
                    $rendiciones = $solicitud->procesoRendicionGastos()->orderBy('id', 'DESC')->get();
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => DatosSolicitudResource::make($solicitud),
                            'rendiciones'   => ProcesoRendicionGastoDetalleResource::collection($rendiciones),
                            'nav'           => $this->navStatusSolicitud($solicitud)
                        )
                    );
                    break;

                case 'archivos':
                    $documentos = $solicitud->documentos()->get();

                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => DatosSolicitudResource::make($solicitud),
                            'archivos'      => ListSolicitudDocumentosResource::collection($documentos),
                            'nav'           => $this->navStatusSolicitud($solicitud)
                        )
                    );
                    break;

                case 'convenio':
                    $convenio  = $solicitud->convenio;
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => DatosSolicitudResource::make($solicitud),
                            'convenio'      => $convenio ? ListConvenioResource::make($convenio) : null,
                            'nav'           => $this->navStatusSolicitud($solicitud)
                        )
                    );
                    break;

                case 'seguimiento':
                    $estados = $solicitud->estados()->get();
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => DatosSolicitudResource::make($solicitud),
                            'estados'       => ListSolicitudStatusResource::collection($estados),
                            'nav'           => $this->navStatusSolicitud($solicitud)
                        )
                    );
                    break;
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }
}
