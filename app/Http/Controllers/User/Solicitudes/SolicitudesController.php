<?php

namespace App\Http\Controllers\User\Solicitudes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Solicitud\AnularSolicitudUserRequest;
use App\Http\Resources\ListSolicitudCalculoAdminResource;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoDetalleResource;
use App\Http\Resources\Solicitud\ListCalculoResoruce;
use App\Http\Resources\Solicitud\ListConvenioResource;
use App\Http\Resources\Solicitud\ListInformeCometidoAdminResource;
use App\Http\Resources\Solicitud\ListSolicitudDocumentosResource;
use App\Http\Resources\Solicitud\ListSolicitudStatusResource;
use App\Http\Resources\User\Solicitud\DatosSolicitudResource;
use App\Http\Resources\User\Solicitud\ListSolicitudResource;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use App\Models\SolicitudFirmante;
use Illuminate\Http\Request;
use App\Traits\StatusSolicitudTrait;
use Illuminate\Support\Facades\Auth;
use App\Traits\FirmaDisponibleTrait;

class SolicitudesController extends Controller
{
    use StatusSolicitudTrait, FirmaDisponibleTrait;

    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function listSolicitudes(Request $request)
    {
        try {
            $auth = Auth::user();
            $solicitudes = Solicitud::where('user_id', $auth->id)
                ->searchInput($request->input)
                ->periodoSolicitud($request->periodo_cometido)
                ->periodoIngreso($request->periodo_ingreso)
                ->estado($request->estados_id)
                ->orderBy('fecha_by_user', 'DESC')
                ->paginate(20);

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'pagination' => [
                        'total'         => $solicitudes->total(),
                        'current_page'  => $solicitudes->currentPage(),
                        'per_page'      => $solicitudes->perPage(),
                        'last_page'     => $solicitudes->lastPage(),
                        'from'          => $solicitudes->firstItem(),
                        'to'            => $solicitudes->lastPage()
                    ],
                    'data'          => ListSolicitudResource::collection($solicitudes)
                )
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function anularSolicitud(AnularSolicitudUserRequest $request)
    {
        try {
            $solicitud = Solicitud::where('uuid', $request->solicitud_uuid)->withCount('documentos')->firstOrFail();
            $this->authorize('anular', $solicitud);

            if ($solicitud) {
                $observacion        = $request->observacion;
                $status             = EstadoSolicitud::STATUS_ANULADO;
                $firma_disponible   = $solicitud->firmantes()->where('posicion_firma', 0)->where('user_id', auth()->user()->id)->first();
                $firma_query        = null;

                $estados[] = [
                    'status'                    => $status,
                    'posicion_firma_s'          => $firma_disponible ? $firma_disponible->posicion_firma : null,
                    'solicitud_id'              => $solicitud->id,
                    'posicion_firma'            => $firma_disponible ? $firma_disponible->posicion_firma : null,
                    's_firmante_id'             => $firma_disponible ? $firma_disponible->id : null,
                    'observacion'               => $observacion,
                    'user_id'                   => auth()->user()->id
                ];
                $create_status  = $solicitud->addEstados($estados);
                $solicitud      = $solicitud->fresh();

                $title      = "Solicitud {$solicitud->codigo} verificada con éxito.";
                $message    = EstadoSolicitud::STATUS_NOM[$solicitud->last_status];

                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => $title,
                        'message'       => $message,
                        'data'          => ListSolicitudResource::make($solicitud)
                    )
                );
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function getSolicitud($uuid, $nav)
    {
        try {
            $solicitud = Solicitud::where('uuid', $uuid)->withCount('documentos')->firstOrFail();
            $navStatus = $this->navStatusSolicitud($solicitud);
            $responseData = [
                'status'    => 'success',
                'title'     => null,
                'message'   => null,
                'data'      => DatosSolicitudResource::make($solicitud),
                'nav'       => $navStatus,
            ];

            switch ($nav) {
                case 'datos':
                    break;
                case 'informes':
                    $responseData['informes'] = ListInformeCometidoAdminResource::collection($solicitud->informes()->orderBy('id', 'DESC')->get());
                    break;
                case 'calculo':
                    $calculo                    = $solicitud->getLastCalculo();
                    $responseData['calculo']    = $calculo ? ListCalculoResoruce::make($calculo) : null;
                    break;
                case 'rendiciones':
                    $responseData['rendiciones'] = ProcesoRendicionGastoDetalleResource::collection($solicitud->procesoRendicionGastos()->orderBy('id', 'DESC')->get());
                    break;
                case 'archivos':
                    $responseData['documentos'] = ListSolicitudDocumentosResource::collection($solicitud->documentos()->get());
                    break;
                case 'convenio':
                    $responseData['convenio']   = $solicitud->convenio ? ListConvenioResource::make($solicitud->convenio) : null;
                    break;
                case 'seguimiento':
                    $responseData['estados'] = ListSolicitudStatusResource::collection($solicitud->estados()->get());
                    break;
                default:
                    return response()->json(['error' => 'Parámetro no encontrado'], 400);
            }
            return response()->json($responseData);
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
}
