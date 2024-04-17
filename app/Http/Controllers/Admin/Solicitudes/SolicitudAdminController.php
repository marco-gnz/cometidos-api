<?php

namespace App\Http\Controllers\Admin\Solicitudes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Solicitud\StatusSolicitudRequest;
use App\Http\Resources\Escala\ListEscalaResource;
use App\Http\Resources\Grupo\ListFirmantesResource;
use App\Http\Resources\ListSolicitudCalculoAdminResource;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoDetalleResource;
use App\Http\Resources\Solicitud\ListActividadesResource;
use App\Http\Resources\Solicitud\ListCalculoResoruce;
use App\Http\Resources\Solicitud\ListConvenioResource;
use App\Http\Resources\Solicitud\ListInformeCometidoAdminResource;
use App\Http\Resources\Solicitud\ListSolicitudAdminResource;
use App\Http\Resources\Solicitud\ListSolicitudCalculoPropuestaAdminResource;
use App\Http\Resources\Solicitud\ListSolicitudCompleteAdminResource;
use App\Http\Resources\Solicitud\ListSolicitudDocumentosResource;
use App\Http\Resources\Solicitud\ListSolicitudStatusResource;
use App\Http\Resources\Solicitud\PropuestaCalculoSolicitud;
use App\Http\Resources\Solicitud\StatusSolicitudResource;
use App\Http\Resources\User\InformeCometido\ListInformeCometidoResource;
use App\Models\Convenio;
use App\Models\Escala;
use App\Models\EstadoSolicitud;
use App\Models\Grupo;
use App\Models\Solicitud;
use App\Models\SolicitudFirmante;
use App\Models\SoliucitudCalculo;
use App\Models\User;
use App\Traits\FirmaDisponibleTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use SebastianBergmann\Type\FalseType;
use Spatie\Permission\Models\Role;
use App\Traits\StatusSolicitudTrait;

class SolicitudAdminController extends Controller
{
    use FirmaDisponibleTrait, StatusSolicitudTrait;

    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function listSolicitudes(Request $request)
    {
        try {
            $params = $request->validate([
                'result' => 'required|in:all,noverify,verify',
            ]);

            $resultSolicitud    = $params['result'];
            $auth               = auth()->user();

            $query = Solicitud::query();
            if ($resultSolicitud === 'noverify') {
                $query->where('status', '!=', Solicitud::STATUS_ANULADO);
                $query->whereDoesntHave('firmantes', function ($q) use ($auth) {
                    $q->where('status', true)->where('is_executed', true)
                        ->where('role_id', '!=', 1);
                    if (!$auth->hasRole('SUPER ADMINISTRADOR')) {
                        $q->where('user_id', $auth->id);
                    }
                });
            } elseif ($resultSolicitud === 'verify') {
                $query->whereHas('firmantes', function ($q) use ($auth) {
                    $q->where('status', true)->where('is_executed', true)
                        ->where('role_id', '!=', 1);
                    if (!$auth->hasRole('SUPER ADMINISTRADOR')) {
                        $q->where('user_id', $auth->id);
                    }
                });
            } elseif ($resultSolicitud === 'all') {
                $query->whereHas('firmantes', function ($q) use ($auth) {
                    $q->where('status', true)
                        ->where('role_id', '!=', 1);
                    if (!$auth->hasRole('SUPER ADMINISTRADOR')) {
                        $q->where('user_id', $auth->id);
                    }
                });
            }

            $solicitudes = $query->orderByDesc('fecha_inicio')->get();

            return response()->json([
                'status'    => 'success',
                'data'      => ListSolicitudAdminResource::collection($solicitudes),
            ]);
        } catch (\Exception $error) {
            return response()->json([
                'status'    => 'error',
                'message'   => $error->getMessage(),
            ], 500);
        }
    }

    public function syncGrupoSolicitud(Request $request)
    {
        try {
            $solicitud = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
            $this->authorize('sincronizargrupo', $solicitud);

            $grupo = Grupo::where('establecimiento_id', $solicitud->funcionario->establecimiento_id)
                ->where('departamento_id', $solicitud->funcionario->departamento_id)
                ->where('sub_departamento_id', $solicitud->funcionario->sub_departamento_id)
                ->first();

            if (!$grupo) {
                return $this->errorResponse("No es posible sincronizar grupo. No existe un grupo de firma dispobile para el funcionario.", 422);
            }

            $solicitud->update([
                'departamento_id'       => $grupo->departamento_id,
                'sub_departamento_id'   => $grupo->sub_departamento_id,
                'establecimiento_id'    => $grupo->establecimiento_id,
                'grupo_id'              => $grupo->id,
            ]);
            $solicitud  = $solicitud->fresh();

            if ($solicitud->grupo) {
                $firmantes = $solicitud->grupo->firmantes()->where('status', true)->get();
                if ($firmantes) {
                    foreach ($firmantes as $firmante) {
                        $status = true;
                        if ($firmante->role_id === 6 || $firmante->role_id === 7) {
                            $status = true;
                            if (!$solicitud->derecho_pago) {
                                $status = false;
                            }
                        }
                        $firmantes_solicitud[] = [
                            'posicion_firma'    => $firmante->posicion_firma,
                            'status'            => $firmante->status,
                            'solicitud_id'      => $solicitud->id,
                            'grupo_id'          => $firmante->grupo_id,
                            'user_id'           => $firmante->user_id,
                            'role_id'           => $firmante->role_id,
                            'status'            => $status
                        ];
                    }
                    $solicitud->addFirmantes($firmantes_solicitud);
                }
            }
            $navStatus  = $this->navStatusSolicitud($solicitud);

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => "Solicitud $solicitud->codigo sincronizada con éxito.",
                    'message'       => null,
                    'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                    'nav'           => $navStatus
                )
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function solicitudFijada($uuid)
    {
        try {
            $solicitud = Solicitud::where('uuid', $uuid)->firstOrFail();
            $user = auth()->user();
            $pinned = $solicitud->users()->where('user_id', $user->id)->wherePivot('is_pinned', true)->exists();

            if ($pinned) {
                $msg = 'desfijada';
                $solicitud->users()->detach($user->id);
            } else {
                $msg = 'fijada';
                $solicitud->users()->attach($user->id, ['is_pinned' => true]);
            }

            $solicitud = $solicitud->fresh();
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => "Solicitud {$solicitud->codigo} $msg con éxito.",
                    'message'       => null,
                    'data'          => ListSolicitudAdminResource::make($solicitud)
                )
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }


    public function findSolicitud($uuid, $nav)
    {
        try {
            $solicitud                      = Solicitud::where('uuid', $uuid)->withCount('documentos')->firstOrFail();
            $navStatus                      = $this->navStatusSolicitud($solicitud);

            switch ($nav) {
                case 'datos':
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                            'nav'           => $navStatus
                        )
                    );
                    break;

                case 'firmantes':
                    $firmantes = $solicitud->firmantes()->get();
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                            'firmantes'     => ListFirmantesResource::collection($firmantes),
                            'nav'           => $navStatus,
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
                            'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                            'calculo'       => $calculo ? ListCalculoResoruce::make($calculo) : null,
                            'nav'           => $navStatus
                        )
                    );

                    break;

                case 'convenio':
                    $convenio  = $solicitud->convenio;
                    $convenios = $this->getConvenios($solicitud);

                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => null,
                            'message'       => null,
                            'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                            'nav'           => $navStatus,
                            'convenios'     => $convenios ? ListConvenioResource::collection($convenios) : null
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
                            'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                            'rendiciones'          => ProcesoRendicionGastoDetalleResource::collection($rendiciones),
                            'nav'           => $navStatus,
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
                            'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                            'documentos'    => ListSolicitudDocumentosResource::collection($documentos),
                            'nav'           => $navStatus,
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
                            'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                            'informes'      => ListInformeCometidoAdminResource::collection($informes),
                            'nav'           => $navStatus,
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
                            'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                            'estados'       => ListSolicitudStatusResource::collection($estados),
                            'nav'           => $navStatus,
                        )
                    );
                    break;
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function updateStatusFirmante($uuid)
    {
        try {
            $firmante = SolicitudFirmante::where('uuid', $uuid)->firstOrFail();
            $this->authorize('update', $firmante);



            $update = $firmante->update([
                'status' => !$firmante->status
            ]);

            if ($update) {
                $solicitud                   = $firmante->solicitud->fresh();
                $total_firmantes_habilitados = $solicitud->firmantes()->where('status', true)->where('posicion_firma', '>', $solicitud->posicion_firma_actual)->count();
                if ($total_firmantes_habilitados === 0) {
                    $last_status_solicitud  = $solicitud->estados()->orderBy('id', 'DESC')->first();
                    if ($last_status_solicitud) {
                        $others_status = [
                            EstadoSolicitud::STATUS_PENDIENTE,
                            EstadoSolicitud::STATUS_MODIFICADA
                        ];
                        $value_last_status_solicitud = $last_status_solicitud->status;
                        if ($value_last_status_solicitud === EstadoSolicitud::STATUS_APROBADO) {
                            $status = Solicitud::STATUS_PROCESADO;
                        } else if ($value_last_status_solicitud === EstadoSolicitud::STATUS_RECHAZADO) {
                            $status = Solicitud::STATUS_EN_PROCESO;
                        } else if (in_array($value_last_status_solicitud, $others_status)) {
                            $status = Solicitud::STATUS_EN_PROCESO;
                        }
                        $solicitud->update([
                            'status' => $status
                        ]);
                    }
                } else {
                    $solicitud->update([
                        'status' => Solicitud::STATUS_EN_PROCESO
                    ]);
                }

                $firmante                   = $firmante->fresh();
                $navStatus                  = $this->navStatusSolicitud($firmante->solicitud);
                $status                     = $firmante->status ? 'habilitado' : 'deshabilitado';
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Firmante {$status} con éxito.",
                        'message'       => null,
                        'data'          => ListFirmantesResource::make($firmante),
                        'nav'           => $navStatus,
                        'is_anulada'    => $navStatus
                    )
                );
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function updateConvenio(Request $request)
    {
        try {
            $corresponde = (int)$request->corresponde;
            $solicitud   = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
            switch ($corresponde) {
                case 1:
                    $convenio                   = Convenio::where('id', $request->convenio_id)->where('active', true)->firstOrFail();
                    $count_solicitudes_convenio = $this->validateNConvenios($convenio, $solicitud);

                    if ($count_solicitudes_convenio >= $convenio->n_viatico_mensual) {
                        return $this->errorResponse("No es posible asociar convenio. Convenio admite {$convenio->n_viatico_mensual} cometidos al mes y este registra un total de {$count_solicitudes_convenio}.", 422);
                    }
                    $update     = $solicitud->update([
                        'convenio_id'       => $convenio->id,
                        'afecta_convenio'   => true
                    ]);

                    $message = "Solicitud de cometido afecta a convenio {$convenio->codigo}";
                    break;
                case 0:
                    $afecta_convenio = (bool)$solicitud->afecta_convenio;
                    if (!$afecta_convenio) {
                        return $this->errorResponse("Solicitud ya se encuentra NO AFECTA a un convenio.", 422);
                    }
                    $update = $solicitud->update([
                        'convenio_id'       => null,
                        'afecta_convenio'   => false
                    ]);

                    $message = 'Solicitud de cometido no afecta a un convenio.';
                    break;
            }

            if ($update) {
                $navStatus = $this->navStatusSolicitud($solicitud);
                $solicitud = $solicitud->fresh();

                $convenio  = $solicitud->convenio;
                $convenios = $this->getConvenios($solicitud);

                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Solicitud modifcada con éxito.",
                        'message'       => $message,
                        'data'          => $convenio ? ListConvenioResource::make($convenio) : null,
                        'nav'           => $navStatus->nav,
                        'is_anulada'    => $navStatus->is_anulada,
                        'convenios'     => $convenios ? ListConvenioResource::collection($convenios) : null
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function validateNConvenios($convenio, $solicitud)
    {
        try {
            $month = Carbon::parse($solicitud->fecha_inicio)->format('m');
            $year  = Carbon::parse($solicitud->fecha_inicio)->format('Y');
            $total_solicitudes = Solicitud::where('convenio_id', $convenio->id)
                ->whereMonth('fecha_inicio', $month)
                ->whereYear('fecha_inicio', $year)
                ->where('last_status', '!=', 4)
                ->count();

            return $total_solicitudes;
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function getConvenios($solicitud)
    {
        try {
            $fecha_inicio       = $solicitud->fecha_inicio;
            $fecha_termino      = $solicitud->fecha_termino;
            $convenios    = Convenio::where('user_id', $solicitud->funcionario->id)
                ->where('active', true)
                ->where('estamento_id', $solicitud->estamento_id)
                ->where('ley_id', $solicitud->ley_id)
                ->where('establecimiento_id', $solicitud->establecimiento_id)
                ->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                    $query->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where('fecha_inicio', '<=', $fecha_inicio)
                            ->where('fecha_termino', '>=', $fecha_inicio);
                    })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where('fecha_inicio', '<=', $fecha_termino)
                            ->where('fecha_termino', '>=', $fecha_termino);
                    })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where('fecha_inicio', '>=', $fecha_inicio)
                            ->where('fecha_termino', '<=', $fecha_termino);
                    });
                })
                ->get();

            return $convenios;
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function permissionsSolicitud($solicitud)
    {
        $permissions = [];

        if ($solicitud->last_status !== 4) {
            array_push($permissions, 'solicitud.edit');
        }

        return $permissions;
    }

    private function getStatus($last_estado, &$total_pendiente, &$total_reasignado, $last_estado_solicitud)
    {
        $status = [
            'is_firma'              => false,
            'status_nom'            => null,
            'status_value'          => null,
            'status_date'           => null,
            'reasignacion'          => false,
            'reasignar_firma_value' => true,
            'type'                  => '#808080',
            'total_pendiente'       => $total_pendiente,
            'is_actual'             => false,
        ];

        if ($last_estado) {
            $status['is_firma']     = $last_estado->status !== null;
            $status['status_nom']   = $last_estado ? EstadoSolicitud::STATUS_NOM[$last_estado->status] : null;
            $status['status_value'] = $last_estado ? $last_estado->status : null;
            $status['status_date']  = $last_estado ? Carbon::parse($last_estado->created_at)->format('d-m-Y H:i') : null;
            $status['reasignacion'] = false;

            if ($last_estado->status === 1) {
                $total_pendiente++;
            }

            /* if ($last_estado->reasignacion) {
                $total_reasignado++;
            } */
        }

        $status['reasignar_firma_value']    = $this->getReasignarFirmaValue($last_estado, $total_reasignado);
        $status['type']                     = $this->getType($last_estado, $total_pendiente);
        $status['is_actual']                = ($last_estado && $this->isActual($last_estado, $last_estado_solicitud)) ? true : false;

        return $status;
    }

    private function getReasignarFirmaValue($last_estado, $total_reasignado)
    {
        $reasignar_firma_value = true;
        if (!$last_estado) {
            $reasignar_firma_value = false;
        } else if (($last_estado) && ($last_estado->status === 1)) {
            $reasignar_firma_value = false;
        } else if ($total_reasignado > 0) {
            $reasignar_firma_value = false;
        } else if (($last_estado) && ($last_estado->reasignacion)) {
            $reasignar_firma_value = false;
        }
        return $reasignar_firma_value;
    }

    private function getType($last_estado, $total_pendiente)
    {
        $type = '#808080';

        if ($last_estado) {
            switch ($last_estado->status) {
                case 1:
                    $type = '#0e6db8';
                    break;
                case 0:
                case 2:
                    if ($total_pendiente <= 0) {
                        $type = '#7ac143';
                    }
                    break;
                case 3:
                case 4:
                    $type = '#dc3545';
                    break;
            }
        }

        return $type;
    }

    private function isActual($last_estado, $last_estado_solicitud)
    {
        return ($last_estado) && ($last_estado_solicitud && $last_estado->id === $last_estado_solicitud->id);
    }

    public function reasignarFirmaSolicitud(Request $request)
    {
        try {
            $solicitud = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
            if (($solicitud) && ($solicitud->last_status === 4)) {
                return $this->errorResponse("No es posible ejecutar firma. Solicitud anulada.", 422);
            }
            $funcionario_firmante   = User::where('uuid', $request->user_uuid)->firstOrFail();
            $firmante               = $solicitud->firmantes()->where('user_id', $funcionario_firmante->id)->where('posicion_firma', $request->posicion_firma)->firstOrFail();
            $auth_user              = Auth::user();
            $estados[] = [
                'status'                    => 1,
                'posicion_firma'            => null,
                'posicion_next_firma'       => $firmante->posicion_firma,
                'reasignacion'              => true,
                'solicitud_id'              => $solicitud->id,
                'user_id'                   => $auth_user ? $auth_user->id : null,
                'role_id'                   => null,
                'user_firmante_id'          => $firmante->user_id,
                'role_firmante_id'          => $firmante->role_id
            ];

            $create_status  = $solicitud->addEstados($estados);
            $solicitud      = $solicitud->fresh();
            $navStatus      = $this->navStatusSolicitud($solicitud);

            if ($create_status) {
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Solicitud {$solicitud->codigo} reasignada.",
                        'message'       => "Firma reasgignada a {$funcionario_firmante->nombre_completo}",
                        'data'          => ListSolicitudStatusResource::make($solicitud),
                        'nav'           => $navStatus,
                        'is_anulada'    => $navStatus
                    )
                );
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function propuestaCalculo(Request $request)
    {
        try {
            $new_escalas    = [];
            $solicitud      = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
            $fecha_inicio   = $solicitud->fecha_inicio;
            $fecha_termino  = $solicitud->fecha_termino;
            $is_escala      = false;

            $escala_solicitud = $this->buscarEscalaSolicitud($solicitud, $fecha_inicio, $fecha_termino);

            $escalas = $this->buscarEscalas($fecha_inicio, $fecha_termino, $solicitud);

            $new_escalas = $escalas->map(function ($escala) use ($escala_solicitud) {
                $escala->{'is_selected'} = ($escala_solicitud && $escala->id === $escala_solicitud->id);
                return $escala;
            });

            $this->asignarMontos($solicitud, $escala_solicitud);

            $solicitud->{'is_escala'} = $escala_solicitud ? true : false;

            return response()->json([
                'status'    => 'success',
                'title'     => null,
                'message'   => null,
                'data'      => PropuestaCalculoSolicitud::make($solicitud),
                'escalas'   => ListEscalaResource::collection($new_escalas),
            ]);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function buscarEscalaSolicitud($solicitud, $fecha_inicio, $fecha_termino)
    {
        $escala = null;
        $ley_solicitud = $solicitud->ley->nombre;

        switch ($ley_solicitud) {
            case '15.076':
            case '19.664':
                $escala = Escala::where(function ($query) use ($fecha_inicio, $fecha_termino) {
                    $query->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where('fecha_inicio', '<=', $fecha_inicio)
                            ->where('fecha_termino', '>=', $fecha_inicio);
                    })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where('fecha_inicio', '<=', $fecha_termino)
                            ->where('fecha_termino', '>=', $fecha_termino);
                    })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where('fecha_inicio', '>=', $fecha_inicio)
                            ->where('fecha_termino', '<=', $fecha_termino);
                    });
                })
                    ->where('ley_id', $solicitud->ley_id)
                    ->first();
                break;

            default:
                $escala = Escala::where(function ($query) use ($fecha_inicio, $fecha_termino) {
                    $query->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where('fecha_inicio', '<=', $fecha_inicio)
                            ->where('fecha_termino', '>=', $fecha_inicio);
                    })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where('fecha_inicio', '<=', $fecha_termino)
                            ->where('fecha_termino', '>=', $fecha_termino);
                    })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                        $query->where('fecha_inicio', '>=', $fecha_inicio)
                            ->where('fecha_termino', '<=', $fecha_termino);
                    });
                })
                    ->where('ley_id', $solicitud->ley_id)
                    ->where('grado_id', $solicitud->grado_id)
                    ->first();
                break;
        }

        return $escala;
    }

    private function buscarEscalas($fecha_inicio, $fecha_termino, $solicitud)
    {
        return Escala::where(function ($query) use ($fecha_inicio, $fecha_termino) {
            $query->where(function ($query) use ($fecha_inicio, $fecha_termino) {
                $query->where('fecha_inicio', '<=', $fecha_inicio)
                    ->where('fecha_termino', '>=', $fecha_inicio);
            })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                $query->where('fecha_inicio', '<=', $fecha_termino)
                    ->where('fecha_termino', '>=', $fecha_termino);
            })->orWhere(function ($query) use ($fecha_inicio, $fecha_termino) {
                $query->where('fecha_inicio', '>=', $fecha_inicio)
                    ->where('fecha_termino', '<=', $fecha_termino);
            });
        })
            ->with('grado')
            ->get()
            ->sortBy('grado.nombre');
    }

    private function asignarMontos($solicitud, $escala_solicitud)
    {
        if ($escala_solicitud) {
            $monto_40 = $solicitud->n_dias_40 ? $escala_solicitud->valor_dia_40 * $solicitud->n_dias_40 : null;
            $monto_100 = $solicitud->n_dias_100 ? $escala_solicitud->valor_dia_100 * $solicitud->n_dias_100 : null;

            $solicitud->{'monto_40'}    = $monto_40;
            $solicitud->{'monto_100'}   = $monto_100;
            $solicitud->{'monto_total'} = $monto_40 + $monto_100;
        }
    }

    public function aplicarCalculo($uuid)
    {
        try {
            $solicitud = Solicitud::where('uuid', $uuid)->firstOrFail();
            $this->authorize('createcalculo', $solicitud);

            if ($this->solicitudAnulada($solicitud)) {
                return $this->errorResponse("No es posible ejecutar acción. Solicitud anulada.", 422);
            }

            $escala = $this->buscarEscalaSolicitud($solicitud, $solicitud->fecha_inicio, $solicitud->fecha_termino);

            if (!$escala) {
                return $this->errorResponse("No existe escala de valores.", 422);
            }

            $data_calculo   = $this->crearDataCalculo($solicitud, $escala);
            if ($this->existeCalculoIdentico($data_calculo)) {
                return $this->errorResponse("Ya existe un cálculo idéntico.", 422);
            }
            $new_calculo    = SoliucitudCalculo::create($data_calculo);

            if ($new_calculo) {
                $solicitud = $solicitud->fresh();
                $this->actualizarSolicitud($solicitud);
                $navStatus = $this->navStatusSolicitud($solicitud);

                $calculo = $solicitud->getLastCalculo();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Valorización aplicada correctamente.",
                        'message'       => null,
                        'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                        'calculo'       => $calculo ? ListCalculoResoruce::make($calculo) : null,
                        'nav'           => $navStatus,
                    )
                );
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function existeCalculoIdentico($data_calculo)
    {
        return SoliucitudCalculo::where($data_calculo)->exists();
    }

    private function actualizarSolicitud($solicitud)
    {
        /* $solicitud->update(['calculo_aplicado' => true]); */
        $solicitud = $solicitud->fresh();

        $solicitud = $solicitud->withCount('calculos')->with(['calculos' => function ($q) {
            $q->orderBy('id', 'DESC')->first();
        }])->first();
    }

    private function crearDataCalculo($solicitud, $escala)
    {
        $monto_40   = $solicitud->n_dias_40 ? $escala->valor_dia_40 * $solicitud->n_dias_40 : null;
        $monto_100  = $solicitud->n_dias_100 ? $escala->valor_dia_100 * $solicitud->n_dias_100 : null;

        return [
            'fecha_inicio'  => $escala->fecha_inicio,
            'fecha_termino' => $escala->fecha_termino,
            'n_dias_100'    => $solicitud->n_dias_100,
            'n_dias_40'     => $solicitud->n_dias_40,
            'valor_dia_40'  => $escala->valor_dia_40,
            'valor_dia_100' => $escala->valor_dia_100,
            'monto_40'      => $monto_40,
            'monto_100'     => $monto_100,
            'solicitud_id'  => $solicitud->id,
            'ley_id'        => $escala->ley_id,
            'grado_id'      => $escala->grado_id,
        ];
    }

    private function errorResponse($message, $statusCode)
    {
        return response()->json([
            'errors' => [
                'solicitud' => $message,
            ],
        ], $statusCode);
    }

    private function solicitudAnulada($solicitud)
    {
        return $solicitud && $solicitud->last_status === 4;
    }

    public function checkActionFirma(Request $request)
    {
        try {
            $status                 = (int)$request->status;
            $solicitud              = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
            $firma_disponible       = $this->firmaDisponible($solicitud, $status);
            $firmantes_disponible   = [];
            if ($status === EstadoSolicitud::STATUS_RECHAZADO) {
                if ($firma_disponible->is_firma) {
                    $firmantes_disponible = $solicitud->firmantes()->whereIn('role_id', [1, 2])->where('status', true)->where('id', '!=', $firma_disponible->id_firma)->where('posicion_firma', '<', $firma_disponible->posicion_firma)->orderBy('posicion_firma', 'ASC')->get();
                }
            }

            if ($status === EstadoSolicitud::STATUS_ANULADO) {
                $firma_disponible       = $this->obtenerFirmaDisponibleSolicitudAnular($solicitud, $status);
            }
            return response()->json(
                array(
                    'status'                    => 'success',
                    'title'                     => $firma_disponible->title,
                    'message'                   => $firma_disponible->message,
                    'is_firma'                  => $firma_disponible->is_firma,
                    'posicion_firma_solicitud'  => $firma_disponible->posicion_firma_solicitud,
                    'posicion_firma'            => $firma_disponible->posicion_firma,
                    'type'                      => $firma_disponible->type,
                    'firmantes'                 => ListFirmantesResource::collection($firmantes_disponible)
                )
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function firmaDisponible($solicitud, $status)
    {
        return $firma_disponible = $this->obtenerFirmaDisponible($solicitud);
    }

    private function positionFirma($solicitud)
    {
        $auth           = Auth::user();
        $last_status    = $solicitud->estados()->where('user_id', $auth->id)->orderBy('posicion_firma', 'DESC')->first();
        $position_firma = null;
        if ($last_status) {
            $position_firma = $last_status->posicion_firma;
        } else {
            $firma = $solicitud->firmantes()->where('user_id', $auth->id)->where('status', true)->orderBy('posicion_firma', 'ASC')->first();
            if ($firma) {
                $position_firma = $firma->posicion_firma;
            }
        }

        return $position_firma;
    }

    private function anularSolicitud($solicitud, $firma_disponible, $observacion)
    {
        try {
            $form = [
                'user_id',
                'fecha_inicio',
                'fecha_termino',
                'hora_llegada',
                'hora_salida',
                'derecho_pago',
                'utiliza_transporte',
                'alimentacion_red',
                'jornada',
                'dentro_pais',
                'tipo_comision_id',
                'actividad_realizada',
                'gastos_alimentacion',
                'gastos_alojamiento',
                'pernocta_lugar_residencia',
                'n_dias_40',
                'n_dias_100',
                'observacion_gastos'
            ];

            $estados[] = [
                'status'                    => EstadoSolicitud::STATUS_ANULADO,
                'posicion_firma_s'          => $firma_disponible ? $firma_disponible->posicion_firma : null,
                'solicitud_id'              => $solicitud->id,
                'posicion_firma'            => $firma_disponible ? $firma_disponible->posicion_firma : null,
                's_firmante_id'             => $firma_disponible ? $firma_disponible->id : null,
                'observacion'               => $observacion
            ];
            return $estados;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function verificarSolicitud($solicitud, $firma_disponible, $firma_reasignada, $status, $observacion, $motivo_id)
    {
        try {
            $form = [
                'user_id',
                'fecha_inicio',
                'fecha_termino',
                'hora_llegada',
                'hora_salida',
                'derecho_pago',
                'utiliza_transporte',
                'alimentacion_red',
                'jornada',
                'dentro_pais',
                'tipo_comision_id',
                'actividad_realizada',
                'gastos_alimentacion',
                'gastos_alojamiento',
                'pernocta_lugar_residencia',
                'n_dias_40',
                'n_dias_100',
                'observacion_gastos'
            ];

            switch ($status) {
                case 1:
                    $status_value = EstadoSolicitud::STATUS_PENDIENTE;
                    break;

                case 2:
                    $status_value = EstadoSolicitud::STATUS_APROBADO;
                    break;

                case 3:
                    $status_value = EstadoSolicitud::STATUS_RECHAZADO;
                    break;
            }
            $is_reasginado = $firma_reasignada ? true : false;
            $estados[] = [
                'status'                    => $status_value,
                'is_reasignado'             => $is_reasginado,
                'r_s_firmante_id'           => $is_reasginado ? $firma_reasignada->id : null,
                'posicion_firma_r_s'        => $is_reasginado ? $firma_reasignada->posicion_firma : null,
                'motivo_rechazo'            => $status === EstadoSolicitud::STATUS_RECHAZADO ? $motivo_id : null,
                'posicion_firma_s'          => $firma_disponible ? $firma_disponible->posicion_firma : null,
                'history_solicitud_old'     => json_encode($solicitud->only($form)),
                'solicitud_id'              => $solicitud->id,
                'posicion_firma'            => $firma_disponible ? $firma_disponible->posicion_firma : null,
                's_firmante_id'             => $firma_disponible ? $firma_disponible->id : null,
                'observacion'               => $observacion
            ];
            return $estados;
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function actionStatusSolicitud(StatusSolicitudRequest $request)
    {
        try {
            $solicitud = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();

            if (($solicitud) && ($solicitud->status === Solicitud::STATUS_ANULADO)) {
                return $this->errorResponse("No es posible ejecutar firma. Solicitud anulada.", 422);
            }

            $status             = (int)$request->status;
            if ($status === EstadoSolicitud::STATUS_ANULADO) {
                $firma_disponible   = $this->obtenerFirmaDisponibleSolicitudAnular($solicitud, $status);
            } else {
                $firma_disponible   = $this->firmaDisponible($solicitud, $status);
            }

            if (($solicitud) && (!$firma_disponible->is_firma)) {
                return $this->errorResponse("No es posible ejecutar firma. Sin firma disponible.", 422);
            }

            $firma_query = null;
            if ($firma_disponible->id_firma) {
                $firma_query = SolicitudFirmante::where('id', $firma_disponible->id_firma)->first();
            }

            $firma_reasignada = null;
            if ($status === EstadoSolicitud::STATUS_PENDIENTE || $status === EstadoSolicitud::STATUS_RECHAZADO) {
                $firma_reasignada = SolicitudFirmante::where('uuid', $request->firmante_uuid)->first();
            }
            switch ($status) {
                case 1:
                case 2:
                case 3:
                    $estados         = $this->verificarSolicitud($solicitud, $firma_query, $firma_reasignada, $status, $request->observacion, $request->motivo_id);
                    $create_status   = $solicitud->addEstados($estados);
                    break;

                case 4:
                    $estados         = $this->anularSolicitud($solicitud, $firma_query, $request->observacion);
                    $create_status   = $solicitud->addEstados($estados);
                    break;
            }
            $solicitud = $solicitud->fresh();
            $navStatus = $this->navStatusSolicitud($solicitud);
            $title = "Solicitud {$solicitud->codigo} verificada con éxito.";
            $message = EstadoSolicitud::STATUS_NOM[$solicitud->last_status];

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => $title,
                    'message'       => $message,
                    'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                    'nav'           => $navStatus
                )
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }
}
