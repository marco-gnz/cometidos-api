<?php

namespace App\Http\Controllers\Admin\Solicitudes;

use App\Http\Controllers\Controller;
use App\Http\Resources\Grupo\ListFirmantesResource;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoDetalleResource;
use App\Http\Resources\Solicitud\ListActividadesResource;
use App\Http\Resources\Solicitud\ListSolicitudAdminResource;
use App\Http\Resources\Solicitud\ListSolicitudCompleteAdminResource;
use App\Http\Resources\Solicitud\ListSolicitudDocumentosResource;
use App\Http\Resources\Solicitud\ListSolicitudStatusResource;
use App\Http\Resources\Solicitud\StatusSolicitudResource;
use App\Models\Solicitud;
use Illuminate\Http\Request;

class SolicitudAdminController extends Controller
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

    public function findSolicitud($uuid, $nav)
    {
        try {
            $new_firmantes      = [];
            $solicitud          = Solicitud::where('uuid', $uuid)->withCount('documentos')->firstOrFail();
            $firmas             = $solicitud->grupo->firmantes()->orderBy('posicion_firma', 'DESC')->get()->unique('user_id');
            $total_pendiente    = 0;

            $last_estado_funcionario = $solicitud->estados()->where('user_id', $solicitud->funcionario->id)->orderBy('id', 'DESC')->first();
            $funcionario = (object) [
                'funcionario'       => $solicitud->funcionario,
                'posicion_firma'    => 0,
                'perfil'            => null,
                'last_estado'       => $last_estado_funcionario,
                'reasignacion'      => ($last_estado_funcionario) && ($last_estado_funcionario->reasignacion) ? true : false
            ];

            array_push($new_firmantes, $funcionario);

            foreach ($firmas as $firma) {
                $last_estado = $solicitud->estados()->where('user_firmante_id', $firma->user_id)->orderBy('id', 'DESC')->first();
                if ($last_estado) {
                    if ($last_estado->status === 1) {
                        $total_pendiente++;
                    }
                }
                if ($total_pendiente > 0) {
                    $fir = (object) [
                        'funcionario'       => $firma->funcionario,
                        'posicion_firma'    => $firma->posicion_firma,
                        'perfil'            => $firma->perfil,
                        'last_estado'       => null,
                        'reasignacion'      => ($last_estado) && ($last_estado->reasignacion) ? true : false
                    ];
                } else {
                    $fir = (object) [
                        'funcionario'       => $firma->funcionario,
                        'posicion_firma'    => $firma->posicion_firma,
                        'perfil'            => $firma->perfil,
                        'last_estado'       => $last_estado,
                        'reasignacion'      => ($last_estado) && ($last_estado->reasignacion) ? true : false
                    ];
                }


                array_push($new_firmantes, $fir);
            }

            $solicitud->{'firmas_status'} = $new_firmantes;

            switch ($nav) {
                case 'datos':
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                        )
                    );
                    break;

                case 'firmantes':
                    $firmantes = $solicitud->grupo->firmantes()->get();
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => ListFirmantesResource::collection($firmantes)
                        )
                    );
                    break;

                case 'rendiciones':
                    $rendiciones = $solicitud->procesoRendicionGastos()->get();
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => ProcesoRendicionGastoDetalleResource::collection($rendiciones)
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
                            'data'          => ListSolicitudDocumentosResource::collection($documentos)
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
                            'data'          => ListSolicitudStatusResource::collection($estados)
                        )
                    );
                    break;
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }
}
