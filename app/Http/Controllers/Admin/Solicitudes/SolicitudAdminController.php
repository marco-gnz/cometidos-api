<?php

namespace App\Http\Controllers\Admin\Solicitudes;

use App\Events\InformeCometidoStatus;
use App\Events\SolicitudChangeStatus;
use App\Events\SolicitudReasignada;
use App\Http\Controllers\Controller;
use App\Http\Requests\Solicitud\Ajuste\StoreAjusteRequest;
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
use App\Models\CalculoAjuste;
use App\Models\Concepto;
use App\Models\Convenio;
use App\Models\Escala;
use App\Models\EstadoCalculoAjuste;
use App\Models\EstadoInformeCometido;
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
use SebastianBergmann\Type\FalseType;
use Spatie\Permission\Models\Role;
use App\Traits\StatusSolicitudTrait;
use Illuminate\Support\Facades\DB;

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
                'result' => 'required|in:none,all,noverify,verify',
            ]);

            $resultSolicitud    = $params['result'];
            $auth               = auth()->user();
            $query              = Solicitud::query();
            switch ($resultSolicitud) {
                case 'noverify':
                    $this->filterNoVerify($query, $auth);
                    break;
                case 'verify':
                    $this->filterVerify($query, $auth);
                    break;
                case 'all':
                    $this->filterAll($query, $auth);
                    break;
                case 'none':
                    if ($auth->hasPermissionTo('solicitudes.ver')) {
                        $this->filterRole($query, $auth);
                    } else {
                        $this->filterAll($query, $auth);
                    }
                    break;
            }

            $query->searchInput($request->input)
                ->firmantesPendiente($request->firmantes_id)
                ->periodoSolicitud($request->periodo_cometido)
                ->periodoIngreso($request->periodo_ingreso)
                ->periodoInformeCometido($request->periodo_informe_cometido)
                ->derechoViatico($request->is_derecho_viatico)
                ->valorizacion($request->is_valorizacion)
                ->rendicion($request->is_rendicion)
                ->informesCometido($request->is_informe_cometido)
                ->archivos($request->is_files)
                ->motivo($request->motivos_id)
                ->lugar($request->lugares_id)
                ->pais($request->paises_id)
                ->medioTransporte($request->medios_transporte)
                ->tipoComision($request->tipo_comision_id)
                ->jornada($request->jornadas_id)
                ->estado($request->estados_id)
                ->estadoInformeCometido($request->estados_informe_id)
                ->estadoIngresoInformeCometido($request->estados_ingreso_informe_id)
                ->isReasignada($request->is_reasignada)
                ->isGrupo($request->is_grupo);

            $solicitudes = $query->orderByDesc('fecha_inicio')->paginate(50);

            return response()->json([
                'status' => 'success',
                'pagination' => [
                    'total'         => $solicitudes->total(),
                    'total_desc'    => $solicitudes->total() > 1 ? "{$solicitudes->total()} resultados" : "{$solicitudes->total()} resultado",
                    'current_page'  => $solicitudes->currentPage(),
                    'per_page'      => $solicitudes->perPage(),
                    'last_page'     => $solicitudes->lastPage(),
                    'from'          => $solicitudes->firstItem(),
                    'to'            => $solicitudes->lastPage()
                ],
                'data' => ListSolicitudAdminResource::collection($solicitudes),
            ]);
        } catch (\Exception $error) {
            return response()->json([
                'status' => 'error',
                'message' => $error->getMessage(),
            ], 500);
        }
    }

    private function filterRole($query, $auth)
    {
        $establecimientos_id    = $auth->establecimientos->pluck('id')->toArray();
        $leyes_id               = $auth->leyes->pluck('id')->toArray();
        $deptos_id              = $auth->departamentos->pluck('id')->toArray();
        if ($establecimientos_id) {
            $query->whereHas('establecimiento', function ($q) use ($establecimientos_id) {
                $q->whereIn('id', $establecimientos_id);
            });
        }

        if ($leyes_id) {
            $query->whereHas('ley', function ($q) use ($leyes_id) {
                $q->whereIn('id', $leyes_id);
            });
        }

        if ($deptos_id) {
            $query->whereHas('departamento', function ($q) use ($deptos_id) {
                $q->whereIn('id', $deptos_id);
            });
        }
    }

    private function filterNoVerify($query, $auth)
    {
        $query->where('status', Solicitud::STATUS_EN_PROCESO)
            ->where(function ($q) use ($auth) {
                $q->whereHas('firmantes', function ($q) use ($auth) {
                    $q->where(function ($q) use ($auth) {
                        $q->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma - 1')
                            ->where('solicituds.is_reasignada', 0)
                            ->where('status', true)
                            ->where('is_executed', false)
                            ->where('role_id', '!=', 1)
                            ->where('user_id', $auth->id);
                    })
                        ->orWhere(function ($q) use ($auth) {
                            $q->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma')
                                ->where('solicituds.is_reasignada', 1)
                                ->where('is_reasignado', true)
                                ->where('status', true)
                                ->where('is_executed', false)
                                ->where('role_id', '!=', 1)
                                ->where('user_id', $auth->id);
                        });
                });
            })->orWhere(function ($q) use ($auth) {
                $q->whereHas('firmantes', function ($q) use ($auth) {
                    $q->whereHas('funcionario.ausentismos', function ($q) use ($auth) {
                        $q->whereHas('subrogantes', function ($q) use ($auth) {
                            $q->where('users.id', $auth->id);
                        })
                            ->whereRaw("DATE(solicituds.fecha_by_user) >= ausentismos.fecha_inicio")
                            ->whereRaw("DATE(solicituds.fecha_by_user) <= ausentismos.fecha_termino")
                            ->where(function ($query) {
                                $query->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma - 1')
                                    ->where('solicituds.is_reasignada', 0)
                                    ->where('status', true)
                                    ->where('is_executed', false)
                                    ->where('role_id', '!=', 1);
                            })->orWhere(function ($query) {
                                $query->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma')
                                    ->where('solicituds.is_reasignada', 1)
                                    ->where('is_reasignado', true)
                                    ->where('status', true)
                                    ->where('is_executed', false)
                                    ->where('role_id', '!=', 1);
                            });
                    });
                });
            })->orWhere(function ($q) use ($auth) {
                $q->whereHas('firmantes', function ($q) use ($auth) {
                    $q->where('is_executed', false)
                        ->whereHas('funcionario.reasignacionAusencias', function ($q) use ($auth) {
                            $q->where('user_subrogante_id', $auth->id)
                                ->where(function ($query) {
                                    $query->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma - 1')
                                        ->where('solicituds.is_reasignada', 0)
                                        ->where('status', true)
                                        ->where('is_executed', false)
                                        ->where('role_id', '!=', 1);
                                })->orWhere(function ($query) {
                                    $query->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma')
                                        ->where('solicituds.is_reasignada', 1)
                                        ->where('is_reasignado', true)
                                        ->where('status', true)
                                        ->where('is_executed', false)
                                        ->where('role_id', '!=', 1);
                                });
                        });
                })->whereHas('reasignaciones', function ($q) use ($auth) {
                    $q->where('user_subrogante_id', $auth->id);
                });
            });
    }

    private function filterVerify($query, $auth)
    {
        $query->whereHas('firmantes', function ($q) use ($auth) {
            $q->where('status', true)->where('is_executed', true)
                ->where('role_id', '!=', 1)
                ->where('user_id', $auth->id);
        })->orWhereHas('firmantes', function ($q) use ($auth) {
            $q->where('is_executed', true)
                ->whereHas('funcionario.ausentismos', function ($q) use ($auth) {
                    $q->whereHas('subrogantes', function ($q) use ($auth) {
                        $q->where('users.id', $auth->id);
                    })->whereRaw("DATE(solicituds.fecha_by_user) >= ausentismos.fecha_inicio")
                        ->whereRaw("DATE(solicituds.fecha_by_user) <= ausentismos.fecha_termino");
                });
        })->orWhere(function ($q) use ($auth) {
            $q->whereHas('firmantes', function ($q) use ($auth) {
                $q->where('is_executed', true)
                    ->whereHas('funcionario.reasignacionAusencias', function ($q) use ($auth) {
                        $q->where('user_subrogante_id', $auth->id);
                    });
            })->whereHas('reasignaciones', function ($q) use ($auth) {
                $q->where('user_subrogante_id', $auth->id);
            });
        });
    }

    private function filterAll($query, $auth)
    {
        $query->whereHas('firmantes', function ($q) use ($auth) {
            $q->where('status', true)
                ->where('role_id', '!=', 1)
                ->where('user_id', $auth->id);
        })->orWhereHas('firmantes.funcionario.ausentismos', function ($q) use ($auth) {
            $q->whereHas('subrogantes', function ($q) use ($auth) {
                $q->where('users.id', $auth->id);
            })->whereRaw("DATE(solicituds.fecha_by_user) >= ausentismos.fecha_inicio")
                ->whereRaw("DATE(solicituds.fecha_by_user) <= ausentismos.fecha_termino");
        })->orWhereHas('reasignaciones', function ($q) use ($auth) {
            $q->where('user_subrogante_id', $auth->id);
        });
    }


    public function syncGrupoSolicitud(Request $request)
    {
        try {
            DB::beginTransaction();
            $solicitud = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
            $this->authorize('sincronizargrupo', $solicitud);

            $grupo = Grupo::where('id', $request->grupo_id)->first();
            $firma_funcionario = $solicitud->firmantes()->where('role_id', 1)->first();
            if ($firma_funcionario) {
                $firma_funcionario->update([
                    'is_reasignado' => false,
                ]);
            }
            if (!$grupo) {
                $solicitud->update([
                    'grupo_id'          => NULL,
                    'is_reasignada'     => false,
                    'last_status'       => EstadoSolicitud::STATUS_INGRESADA,
                    'calculo_aplicado'  => false,
                    'total_firmas'      => 1
                ]);

                $solicitud->firmantes()->where('role_id', '!=', 1)->delete();
                $solicitud = $solicitud->fresh();
            } else {
                $solicitud->update([
                    'grupo_id'          => $grupo->id,
                    'is_reasignada'     => false,
                    'last_status'       => EstadoSolicitud::STATUS_INGRESADA,
                    'calculo_aplicado'  => false,
                    'total_firmas'      => 1
                ]);
                $solicitud  = $solicitud->fresh();

                if ($solicitud->grupo) {
                    if ($solicitud->firmantes) {

                        $solicitud->firmantes()->where('role_id', '!=', 1)->delete();
                    }
                    $solicitud = $solicitud->fresh();

                    if (count($solicitud->firmantes) === 1) {
                        $firmantes_solicitud = [];
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
                                    'status'            => $status,
                                    'permissions_id'    => $this->getPermissions($firmante->role_id, $solicitud)
                                ];
                            }
                            $solicitud->addFirmantes($firmantes_solicitud);
                        }
                    }
                }
            }
            $navStatus  = $this->navStatusSolicitud($solicitud);
            DB::commit();
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => "Solicitud $solicitud->codigo sincronizada con éxito.",
                    'message'       => null,
                    'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                    'nav'           => $navStatus,
                    'solicitudList' => ListSolicitudAdminResource::make($solicitud)
                )
            );
        } catch (\Exception $error) {
            DB::rollback();
            return response()->json(['error' => $error->getMessage()], 500);
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
            $solicitud = Solicitud::where('uuid', $uuid)->withCount('documentos')->firstOrFail();
            $navStatus = $this->navStatusSolicitud($solicitud);
            $responseData = [
                'status'    => 'success',
                'title'     => null,
                'message'   => null,
                'data'      => ListSolicitudCompleteAdminResource::make($solicitud),
                'nav'   => $navStatus,
            ];

            switch ($nav) {
                case 'datos':
                    break;
                case 'firmantes':
                    $responseData['firmantes'] = ListFirmantesResource::collection($solicitud->firmantes()->orderBy('posicion_firma', 'ASC')->get());
                    break;
                case 'calculo':
                    $calculo                    = $solicitud->getLastCalculo();
                    $responseData['calculo']    = $calculo ? ListCalculoResoruce::make($calculo) : null;
                    break;
                case 'convenio':
                    $responseData['convenio']   = $solicitud->convenio ? ListConvenioResource::make($solicitud->convenio) : null;
                    $responseData['convenios']  = $this->getConvenios($solicitud) ? ListConvenioResource::collection($this->getConvenios($solicitud)) : null;
                    break;
                case 'rendiciones':
                    $responseData['rendiciones'] = ProcesoRendicionGastoDetalleResource::collection($solicitud->procesoRendicionGastos()->orderBy('id', 'DESC')->get());
                    break;
                case 'archivos':
                    $responseData['documentos'] = ListSolicitudDocumentosResource::collection($solicitud->documentos()->get());
                    break;
                case 'informes':
                    $responseData['informes'] = ListInformeCometidoAdminResource::collection($solicitud->informes()->orderBy('id', 'DESC')->get());
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
            $this->authorize('createconvenio', $solicitud);
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
                        'message'       => null,
                        'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                        'convenio'      => $convenio ? ListConvenioResource::make($convenio) : null,
                        'nav'           => $navStatus,
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
                ->where('last_status', '!=', EstadoSolicitud::STATUS_ANULADO)
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
            $convenios    = Convenio::where('user_id', $solicitud->user_id)
                ->where('active', true)
                ->where('estamento_id', $solicitud->estamento_id)
                ->where('ley_id', $solicitud->ley_id)
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
                'status'                    => EstadoSolicitud::STATUS_PENDIENTE,
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
        $ley_solicitud = $solicitud->ley->id;

        switch ($ley_solicitud) {
            case 1:
            case 4:
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

            $last_cuenta_bancaria = $solicitud->funcionario->lastCuentaBancaria();

            if (!$last_cuenta_bancaria) {
                return $this->errorResponse("Funcionario no registra cuenta bancaria habilitada o algún medio de pago.", 422);
            }

            $solicitud->update([
                'cuenta_bancaria_id' => $last_cuenta_bancaria->id
            ]);

            $data_calculo   = $this->crearDataCalculo($solicitud, $escala);
            if ($this->existeCalculoIdentico($data_calculo)) {
                return $this->errorResponse("Ya existe un cálculo idéntico.", 422);
            }
            $new_calculo    = SoliucitudCalculo::create($data_calculo);

            if ($new_calculo) {
                $solicitud = $solicitud->fresh();
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

    public function previewAjuste(Request $request)
    {
        try {
            $solicitud_calculo = SoliucitudCalculo::where('uuid', $request->calculo_uuid)->firstOrFail();
            $tipo_ajuste = (int)$request->tipo_ajuste;
            switch ($tipo_ajuste) {
                case 0:
                    #ajuste días
                    $dias_40_new        = (int)$request->n_dias_40;
                    $dias_100_new       = (int)$request->n_dias_100;

                    $monto_dias_40_new  = $solicitud_calculo->valor_dia_40 * $dias_40_new;
                    $monto_dias_100_new = $solicitud_calculo->valor_dia_100 * $dias_100_new;
                    $monto_total_new    = $monto_dias_40_new + $monto_dias_100_new;
                    $data = (object) [
                        'dias_40_calculo'       => $solicitud_calculo->n_dias_40,
                        'dias_100_calculo'      => $solicitud_calculo->n_dias_100,
                        'monto_40_calculo'      => "$" . number_format($solicitud_calculo->monto_40, 0, ',', '.'),
                        'monto_100_calculo'     => "$" . number_format($solicitud_calculo->monto_100, 0, ',', '.'),

                        'dias_40_new'           => $dias_40_new,
                        'dias_100_new'          => $dias_100_new,
                        'monto_dias_40_new'     => "$" . number_format($monto_dias_40_new, 0, ',', '.'),
                        'monto_dias_100_new'    => "$" . number_format($monto_dias_100_new, 0, ',', '.'),

                        'monto_total'           => "$" . number_format($solicitud_calculo->monto_total, 0, ',', '.'),
                        'monto_total_new'       => "$" . number_format($solicitud_calculo->monto_total + $monto_total_new, 0, ',', '.')
                    ];
                    break;

                default:
                    $monto_40_new       = (int)$request->monto_40;
                    $monto_100_new      = (int)$request->monto_100;
                    $sum_40_100         = $monto_40_new + $monto_100_new;

                    $data = (object) [
                        'monto_40_calculo'      => "$" . number_format($solicitud_calculo->monto_40, 0, ',', '.'),
                        'monto_100_calculo'     => "$" . number_format($solicitud_calculo->monto_100, 0, ',', '.'),

                        'monto_40_new'          => "$" . number_format($monto_40_new, 0, ',', '.'),
                        'monto_100_new'         => "$" . number_format($monto_100_new, 0, ',', '.'),

                        'monto_total'           => "$" . number_format($solicitud_calculo->monto_total, 0, ',', '.'),
                        'monto_total_new'       => "$" . number_format($solicitud_calculo->monto_total + $sum_40_100, 0, ',', '.')
                    ];
                    break;
            }

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => $data,
                )
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function storeAjuste(StoreAjusteRequest $request)
    {
        try {
            $solicitud_calculo = SoliucitudCalculo::where('uuid', $request->calculo_uuid)->firstOrFail();
            $this->authorize('createcalculoajuste', $solicitud_calculo->solicitud);
            $form = [
                'tipo_ajuste',
                'n_dias_40',
                'n_dias_100',
                'monto_40',
                'monto_100',
                'observacion'
            ];

            $isValidateValor = $this->isValidateValor($request, $solicitud_calculo);

            if (!$isValidateValor) {
                return response()->json([
                    'errors' => [
                        'observacion'  => ['No es posible ingresar ajuste. La valorización total no puede ser $0']
                    ]
                ], 422);
            }

            $new_ajuste = $solicitud_calculo->ajustes()->create($request->only($form));
            if ($new_ajuste) {
                $solicitud = $solicitud_calculo->solicitud->fresh();
                $calculo   = $new_ajuste->calculo->fresh();
                $navStatus = $this->navStatusSolicitud($solicitud);

                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Ajuste ingresado con éxito.",
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

    private function isValidateValor($request, $solicitud_calculo)
    {
        $total_nuevo_ajuste = 0;
        $tipo_ajuste = (int)$request->tipo_ajuste;

        if ($tipo_ajuste === CalculoAjuste::TYPE_0) {
            $dmontov40          = $solicitud_calculo->valor_dia_40 * (int)$request->n_dias_40;
            $dmontov100         = $solicitud_calculo->valor_dia_100 * (int)$request->n_dias_100;
            $total_nuevo_ajuste = $dmontov40 +  $dmontov100;
        } else if ($tipo_ajuste === CalculoAjuste::TYPE_1) {
            $montov40            = (int)$request->monto_40;
            $montov100           = (int)$request->monto_100;
            $total_nuevo_ajuste  = $montov40 +  $montov100;
        }

        $total_valorizacion             = $solicitud_calculo->valorizacionTotalAjusteMonto()->total_valorizacion_value;
        $total_valorizacion_mas_ajuste  = $total_valorizacion + $total_nuevo_ajuste;
        if ($total_valorizacion_mas_ajuste <= 0) {
            return false;
        }
        return true;
    }

    public function deleteAjuste($uuid)
    {
        try {
            $ajuste = CalculoAjuste::where('uuid', $uuid)->firstOrFail();
            $this->authorize('deletecalculoajuste', $ajuste->calculo->solicitud);
            $delete = $ajuste->delete();
            if ($delete) {
                $calculo        = $ajuste->calculo->fresh();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Ajuste eliminado con éxito.",
                        'message'       => null,
                        'calculo'       => $calculo ? ListCalculoResoruce::make($calculo) : null,
                    )
                );
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
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
        return $solicitud && $solicitud->status === Solicitud::STATUS_ANULADO;
    }

    public function checkActionFirma(Request $request)
    {
        try {
            $status                 = (int)$request->status;
            $solicitud              = Solicitud::where('uuid', $request->solicitud_uuid)->firstOrFail();
            $firma_disponible       = $this->obtenerFirmaDisponible($solicitud, 'solicitud.firma.validar', $status);
            $firmantes_disponible   = [];
            if ($status === EstadoSolicitud::STATUS_RECHAZADO) {
                if ($firma_disponible->is_firma) {
                    $id_permission_solicitud_editar     = $this->idPermission('solicitud.datos.editar-solicitud');
                    $id_permission_valorizacion_crear   = $this->idPermission('solicitud.valorizacion.crear');
                    $id_permission_convenio_crear       = $this->idPermission('solicitud.convenio.crear');
                    $id_permission_ajustes_crear        = $this->idPermission('solicitud.ajuste.crear');
                    $ids_permissions = [
                        $id_permission_solicitud_editar,
                        $id_permission_valorizacion_crear,
                        $id_permission_convenio_crear,
                        $id_permission_ajustes_crear
                    ];
                    $filtered_permissions_id = array_filter($ids_permissions, function ($value) {
                        return $value !== null;
                    });
                    $firmantes_disponible = $solicitud->firmantes()
                        ->where('id', '!=', $firma_disponible->id_firma)
                        ->where('status', true)
                        ->where('posicion_firma', '<', $firma_disponible->posicion_firma)
                        ->where(function ($query) use ($filtered_permissions_id) {
                            foreach ($filtered_permissions_id as $permission_id) {
                                $query->orWhereRaw("JSON_CONTAINS(permissions_id, CAST('$permission_id' AS JSON))");
                            }
                        })
                        ->orderBy('posicion_firma', 'ASC')
                        ->get();
                }
            }

            if ($status === EstadoSolicitud::STATUS_ANULADO) {
                $firma_disponible       = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.firma.anular');
            }
            return response()->json(
                array(
                    'status'                    => 'success',
                    'title'                     => $firma_disponible->title,
                    'message'                   => $firma_disponible->message,
                    'is_firma'                  => $firma_disponible->is_firma,
                    'if_buttom'                 => $firma_disponible->if_buttom,
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

    /* private function firmaDisponible($solicitud, $status)
    {
        return $firma_disponible = $this->obtenerFirmaDisponible($solicitud);
    } */

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
                'posicion_firma_s'          => $firma_disponible->is_firma ? $firma_disponible->posicion_firma : null,
                'solicitud_id'              => $solicitud->id,
                'posicion_firma'            => $firma_disponible->is_firma ? $firma_disponible->posicion_firma : null,
                's_firmante_id'             => $firma_disponible->is_firma ? $firma_disponible->id_firma : null,
                'user_id'                   => $firma_disponible->is_firma ? $firma_disponible->id_user_ejecuted_firma : null,
                'is_subrogante'             => $firma_disponible->is_firma ? $firma_disponible->is_subrogante : false,
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
                's_firmante_id'             => $firma_disponible ? $firma_disponible->id_firma : null,
                'user_id'                   => $firma_disponible ? $firma_disponible->id_user_ejecuted_firma : null,
                'is_subrogante'             => $firma_disponible ? $firma_disponible->is_subrogante : false,
                'observacion'               => $observacion,
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
                $firma_disponible   = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.firma.anular');
            } else {
                $firma_disponible   = $this->obtenerFirmaDisponible($solicitud, 'solicitud.firma.validar');
            }

            $firma_reasignada = null;
            if ($status === EstadoSolicitud::STATUS_PENDIENTE || $status === EstadoSolicitud::STATUS_RECHAZADO) {
                $firma_reasignada = SolicitudFirmante::where('uuid', $request->firmante_uuid)->first();
            }
            switch ($status) {
                case 1:
                case 2:
                case 3:
                    $estados          = $this->verificarSolicitud($solicitud, $firma_disponible, $firma_reasignada, $status, $request->observacion, $request->motivo_id);
                    $create_status    = $solicitud->addEstados($estados);
                    $informe_cometido = $solicitud->informeCometido();

                    if (($informe_cometido) && ($informe_cometido->last_status === EstadoInformeCometido::STATUS_INGRESADA)) {
                        $this->aprobarInformeCometidoAutomatico($solicitud);
                    }

                    break;

                case 4:
                    $this->authorize('anularAdmin', $solicitud);
                    $estados         = $this->anularSolicitud($solicitud, $firma_disponible, $request->observacion);
                    $create_status   = $solicitud->addEstados($estados);
                    break;
            }
            $solicitud = $solicitud->fresh();

            $last_status = $solicitud->estados()->orderBy('id', 'DESC')->first();

            if (($last_status) && (!$last_status->is_reasignado)) {
                $ids_roles_aprobado     = [2, 3, 7, 10];
                $ids_roles_rechazado    = [2, 3, 4, 5, 6, 7, 10];
                $ids_roles_anulado      = [3, 7, 8];
                $emails_copy            = [];
                if ($last_status->status === EstadoSolicitud::STATUS_APROBADO && in_array($last_status->s_role_id, $ids_roles_aprobado)) {
                    $is_avion = $solicitud->transportes()->where('solicitud_transporte.transporte_id', 1)->exists();
                    if ($last_status->s_role_id === 3 && $is_avion) {
                        $name     = 'ABASTECIMIENTO';
                        $concepto = Concepto::where('nombre', $name)->first();
                        if ($concepto) {
                            $conceptoEstablecimiento = $concepto->conceptosEstablecimientos()
                                ->where('establecimiento_id', $solicitud->establecimiento_id)
                                ->first();

                            $emails_copy = $conceptoEstablecimiento->funcionarios()->pluck('users.email')->toArray();
                        }
                    }
                    SolicitudChangeStatus::dispatch($solicitud, $last_status, $emails_copy);
                } else if ($last_status->status === EstadoSolicitud::STATUS_RECHAZADO && in_array($last_status->s_role_id, $ids_roles_rechazado)) {
                    SolicitudChangeStatus::dispatch($solicitud, $last_status, $emails_copy);
                } else if ($last_status->status === EstadoSolicitud::STATUS_ANULADO) {
                    if ($solicitud->derecho_pago) {
                        $emails_copy = $solicitud->firmantes()->whereIn('role_id', $ids_roles_anulado)->with('funcionario')->get()->pluck('funcionario.email')->toArray();
                    }
                    SolicitudChangeStatus::dispatch($solicitud, $last_status, $emails_copy);
                }
            } else if (($last_status) && ($last_status->is_reasignado)) {
                SolicitudReasignada::dispatch($solicitud, $last_status);
            }

            $navStatus = $this->navStatusSolicitud($solicitud);
            $title = "Solicitud {$solicitud->codigo} verificada con éxito.";
            $message = EstadoSolicitud::STATUS_NOM[$solicitud->last_status];
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => $title,
                    'message'       => $message,
                    'data'          => ListSolicitudCompleteAdminResource::make($solicitud),
                    'nav'           => $navStatus,
                    'solicitudList' => ListSolicitudAdminResource::make($solicitud)
                )
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function aprobarInformeCometidoAutomatico($solicitud)
    {
        $firma_disponible = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.informes.validar');
        if ($firma_disponible->is_firma) {
            $informeCometido                    = $solicitud->informeCometido();
            $status                             = EstadoInformeCometido::STATUS_APROBADO;

            $estados[] = [
                'status'                    => $status,
                'informe_cometido_id'       => $informeCometido->id,
                'observacion'               => "GECOM: Informe aprobado automáticamente al aprobar cometido N° {$solicitud->codigo}",
                'is_subrogante'             => $firma_disponible->is_subrogante,
                'role_id'                   => $firma_disponible->is_firma ? $firma_disponible->firma->role_id : null,
                'posicion_firma'            => $firma_disponible->is_firma ? $firma_disponible->firma->posicion_firma : null
            ];

            $create_status      = $informeCometido->addEstados($estados);
            $informeCometido    = $informeCometido->fresh();

            if ($create_status) {
                $last_status = $informeCometido->estados()->orderBy('id', 'DESC')->first();
                InformeCometidoStatus::dispatch($last_status);
            }
        }
    }
}
