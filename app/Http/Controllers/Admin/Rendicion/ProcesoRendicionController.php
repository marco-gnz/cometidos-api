<?php

namespace App\Http\Controllers\Admin\Rendicion;

use App\Events\ProcesoRendicionGastoStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProcesoRendicion\StatusProcesoRendicionRequest;
use App\Http\Requests\Rendicion\StatusRendicionRequest;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoDetalleResource;
use App\Http\Resources\Rendicion\ProcesoRendicionGastoResource;
use App\Http\Resources\Rendicion\RendicionGastoResource;
use App\Models\EstadoProcesoRendicionGasto;
use App\Models\EstadoRendicionGasto;
use App\Models\ProcesoRendicionGasto;
use App\Models\RendicionGasto;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\FirmaDisponibleTrait;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Traits\StatusSolicitudTrait;

class ProcesoRendicionController extends Controller
{
    use FirmaDisponibleTrait, StatusSolicitudTrait;

    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getProcesoRendiciones(Request $request)
    {
        try {
            $params = $request->validate([
                'result' => 'required|in:none,all,noverify,verify',
            ]);

            $resultSolicitud = $params['result'];

            $auth = auth()->user();
            $query = ProcesoRendicionGasto::query();

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
                    if ($auth->hasPermissionTo('rendiciones.ver')) {
                        $this->filterRole($query, $auth);
                    } else {
                        $this->filterAll($query, $auth);
                    }
                    break;
            }

            $query->searchInput($request->input, $request->in)
                ->periodoSolicitud($request->periodo_cometido)
                ->periodoIngresoSolicitud($request->periodo_ingreso_cometido)
                ->periodoIngresoProceso($request->periodo_ingreso_rendicion)
                ->periodoPagoRendicion($request->periodo_pago_rendicion)
                ->derechoViatico($request->is_derecho_viatico)
                ->archivos($request->is_files)
                ->motivo($request->motivos_id)
                ->lugar($request->lugares_id)
                ->pais($request->paises_id)
                ->tipoComision($request->tipo_comision_id)
                ->jornada($request->jornadas_id)
                ->estado($request->estados_id)
                ->concepto($request->conceptos_id)
                ->estadoRendicion($request->estados_rendicion_id);

            $sort           = $request->sort; //column.asc || column.desc
            $parts          = explode('.', $sort);
            $column         = $parts[0];
            $direction      = $parts[1];

            switch ($sort) {
                case 'fecha_inicio.asc':
                case 'fecha_inicio.desc':
                    $proceso_rendiciones = $query->select('proceso_rendicion_gastos.*')
                        ->join('solicituds', 'solicituds.id', '=', 'proceso_rendicion_gastos.solicitud_id')
                        ->orderBy('solicituds.fecha_inicio', $direction);
                    break;

                case 'apellidos.asc':
                case 'apellidos.desc':
                    $proceso_rendiciones = $query->select('proceso_rendicion_gastos.*')
                        ->join('solicituds', 'solicituds.id', '=', 'proceso_rendicion_gastos.solicitud_id')
                        ->join('users', 'users.id', '=', 'solicituds.user_id')
                        ->orderBy('users.apellidos', $direction);
                    break;

                case 'monto_solicitado.asc':
                case 'monto_solicitado.desc':
                    $proceso_rendiciones = $query->select('proceso_rendicion_gastos.*')
                        ->leftJoin('rendicion_gastos', function ($join) {
                            $join->on('rendicion_gastos.proceso_rendicion_gasto_id', '=', 'proceso_rendicion_gastos.id')
                                ->where('rendicion_gastos.rinde_gasto', true);
                        })
                        ->selectRaw('COALESCE(SUM(rendicion_gastos.mount), 0) as total_monto')
                        ->groupBy('proceso_rendicion_gastos.id')
                        ->orderBy('total_monto', $direction);
                    break;

                case 'monto_aprobado.asc':
                case 'monto_aprobado.desc':
                    $proceso_rendiciones = $query->select('proceso_rendicion_gastos.*')
                        ->leftJoin('rendicion_gastos', function ($join) {
                            $join->on('rendicion_gastos.proceso_rendicion_gasto_id', '=', 'proceso_rendicion_gastos.id')
                                ->where('rendicion_gastos.rinde_gasto', true)
                                ->where('rendicion_gastos.last_status', 1);
                        })
                        ->selectRaw('COALESCE(SUM(rendicion_gastos.mount_real), 0) as total_monto_aprobado')
                        ->groupBy('proceso_rendicion_gastos.id')
                        ->orderBy('total_monto_aprobado', $direction);
                    break;

                default:
                    $proceso_rendiciones = $query->orderBy($column, $direction);
            }

            $proceso_rendiciones = $proceso_rendiciones->paginate(50);

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'pagination' => [
                        'total'         => $proceso_rendiciones->total(),
                        'total_desc'    => $proceso_rendiciones->total() > 1 ? "{$proceso_rendiciones->total()} resultados" : "{$proceso_rendiciones->total()} resultado",
                        'current_page'  => $proceso_rendiciones->currentPage(),
                        'per_page'      => $proceso_rendiciones->perPage(),
                        'last_page'     => $proceso_rendiciones->lastPage(),
                        'from'          => $proceso_rendiciones->firstItem(),
                        'to'            => $proceso_rendiciones->lastPage()
                    ],
                    'data'          => ProcesoRendicionGastoResource::collection($proceso_rendiciones)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function filterNoVerify($query, $auth)
    {
        try {
            $permissions_rendiciones    = [24, 25, 64];
            $status_proceso_rendicion   = [
                EstadoProcesoRendicionGasto::STATUS_APROBADO_N,
                EstadoProcesoRendicionGasto::STATUS_APROBADO_S,
                EstadoProcesoRendicionGasto::STATUS_ANULADO,
                EstadoProcesoRendicionGasto::STATUS_RECHAZADO
            ];
            $query->where(function ($query) use ($auth, $permissions_rendiciones) {
                $query->whereHas('solicitud.firmantes', function ($q) use ($auth, $permissions_rendiciones) {
                    $q->whereRaw('proceso_rendicion_gastos.posicion_firma_ok = solicitud_firmantes.posicion_firma')
                        ->where('role_id', '!=', 1)
                        ->where('user_id', $auth->id);
                })->orWhereHas('solicitud.firmantes', function ($q) use ($auth) {
                    $q->whereIn('is_executed', [true, false])
                        ->whereRaw('proceso_rendicion_gastos.posicion_firma_ok = solicitud_firmantes.posicion_firma')
                        ->whereHas('funcionario.ausentismos', function ($q) use ($auth) {
                            $q->whereHas('subrogantes', function ($q) use ($auth) {
                                $q->where('users.id', $auth->id);
                            })->whereRaw("DATE(proceso_rendicion_gastos.fecha_last_firma) >= ausentismos.fecha_inicio")
                                ->whereRaw("DATE(proceso_rendicion_gastos.fecha_last_firma) <= ausentismos.fecha_termino");
                        });
                })->orWhere(function ($q) use ($auth) {
                    $q->whereHas('solicitud.firmantes', function ($q) use ($auth) {
                        $q->whereIn('is_executed', [true, false])
                            ->whereRaw('proceso_rendicion_gastos.posicion_firma_ok = solicitud_firmantes.posicion_firma')
                            ->whereHas('funcionario.reasignacionAusencias', function ($q) use ($auth) {
                                $q->where('user_subrogante_id', $auth->id)
                                    ->whereHas('solicitudes', function ($query) {
                                        $query->whereRaw('solicituds.id = solicitud_firmantes.solicitud_id');
                                    });
                            });
                    });
                });
            });

            $query->where(function ($query) use ($status_proceso_rendicion) {
                $query->whereHas('solicitud', function ($q) use ($status_proceso_rendicion) {
                    $q->where('status', Solicitud::STATUS_PROCESADO);
                })->whereNotIn('status', $status_proceso_rendicion);
            });
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    private function filterVerify($query, $auth)
    {
        try {
            $permissions_rendiciones    = [24, 25, 64];
            $query->where(function ($query) use ($auth, $permissions_rendiciones) {
                $query->whereHas('solicitud.firmantes', function ($q) use ($auth, $permissions_rendiciones) {
                    $q->where('role_id', '!=', 1)
                        ->where('user_id', $auth->id);
                })->orWhereHas('solicitud.firmantes', function ($q) use ($auth) {
                    $q->whereIn('is_executed', [true, false])
                        ->whereHas('funcionario.ausentismos', function ($q) use ($auth) {
                            $q->whereHas('subrogantes', function ($q) use ($auth) {
                                $q->where('users.id', $auth->id);
                            })->whereRaw("DATE(proceso_rendicion_gastos.fecha_last_firma) >= ausentismos.fecha_inicio")
                                ->whereRaw("DATE(proceso_rendicion_gastos.fecha_last_firma) <= ausentismos.fecha_termino");
                        });
                })->orWhere(function ($q) use ($auth) {
                    $q->whereHas('solicitud.firmantes', function ($q) use ($auth) {
                        $q->whereIn('is_executed', [true, false])
                            ->whereHas('funcionario.reasignacionAusencias', function ($q) use ($auth) {
                                $q->where('user_subrogante_id', $auth->id)
                                    ->whereHas('solicitudes', function ($query) {
                                        $query->whereRaw('solicituds.id = solicitud_firmantes.solicitud_id');
                                    });
                            });
                    });
                })->orWhereHas('estados', function ($q) use ($auth) {
                    $q->where('user_id_by', $auth->id)
                        ->where('role_id', '!=', 1);
                });
            });

            $query->whereHas('estados', function ($q) use ($auth) {
                $q->where('user_id_by', $auth->id);
            });
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    private function filterAll($query, $auth)
    {
        try {
            $permissions_rendiciones    = [24, 25, 64];
            $query->where(function ($query) use ($auth, $permissions_rendiciones) {
                $query->whereHas('solicitud.firmantes', function ($q) use ($auth, $permissions_rendiciones) {
                    $q->where('role_id', '!=', 1)
                        ->where('user_id', $auth->id);
                })->orWhereHas('solicitud.firmantes', function ($q) use ($auth) {
                    $q->whereIn('is_executed', [true, false])
                        ->whereHas('funcionario.ausentismos', function ($q) use ($auth) {
                            $q->whereHas('subrogantes', function ($q) use ($auth) {
                                $q->where('users.id', $auth->id);
                            })->whereRaw("DATE(proceso_rendicion_gastos.fecha_last_firma) >= ausentismos.fecha_inicio")
                                ->whereRaw("DATE(proceso_rendicion_gastos.fecha_last_firma) <= ausentismos.fecha_termino");
                        });
                })->orWhere(function ($q) use ($auth) {
                    $q->whereHas('solicitud.firmantes', function ($q) use ($auth) {
                        $q->whereIn('is_executed', [true, false])
                            ->whereHas('funcionario.reasignacionAusencias', function ($q) use ($auth) {
                                $q->where('user_subrogante_id', $auth->id);
                            });
                    })->whereHas('solicitud.reasignaciones', function ($q) use ($auth) {
                        $q->where('user_subrogante_id', $auth->id);
                    });
                })->orWhereHas('estados', function ($q) use ($auth) {
                    $q->where('user_id_by', $auth->id)
                        ->where('role_id', '!=', 1);
                });
            });
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    private function filterRole($query, $auth)
    {
        $establecimientos_id    = $auth->establecimientos->pluck('id')->toArray();
        $leyes_id               = $auth->leyes->pluck('id')->toArray();
        $deptos_id              = $auth->departamentos->pluck('id')->toArray();
        $transportes_id         = $auth->transportes->pluck('id')->toArray();
        $tip_comision_id        = $auth->tipoComisiones->pluck('id')->toArray();

        if ($establecimientos_id) {
            $query->whereHas('solicitud.establecimiento', function ($q) use ($establecimientos_id) {
                $q->whereIn('id', $establecimientos_id);
            });
        }

        if ($leyes_id) {
            $query->whereHas('solicitud.ley', function ($q) use ($leyes_id) {
                $q->whereIn('id', $leyes_id);
            });
        }

        if ($transportes_id) {
            $query->whereHas('solicitud.transportes', function ($q) use ($transportes_id) {
                $q->whereIn('transportes.id', $transportes_id);
            });
        }

        if ($deptos_id) {
            $query->whereHas('solicitud.departamento', function ($q) use ($deptos_id) {
                $q->whereIn('id', $deptos_id);
            });
        }

        if ($tip_comision_id) {
            $query->whereHas('solicitud.tipoComision', function ($q) use ($tip_comision_id) {
                $q->whereIn('id', $tip_comision_id);
            });
        }
    }

    public function getProcesoRendicion($uuid)
    {
        try {
            $rendicion = ProcesoRendicionGasto::where('uuid', $uuid)->firstOrFail();
            $navStatus  = $this->navStatusRendicion($rendicion);
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => ProcesoRendicionGastoDetalleResource::make($rendicion),
                    'nav'           => $navStatus
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function statusProcesoRenicion(StatusProcesoRendicionRequest $request)
    {
        try {
            $proceso_rendicion_gasto    = ProcesoRendicionGasto::where('uuid', $request->uuid)->firstOrFail();
            $status                     = (int)$request->status;
            $observacion                = $request->observacion;
            switch ($status) {
                case 1:
                    $this->authorize('anular', $proceso_rendicion_gasto);
                    $this->anularProcesoRendicion($proceso_rendicion_gasto, $observacion);
                    break;

                case 2:
                    $this->authorize('aprobar', $proceso_rendicion_gasto);
                    if ($proceso_rendicion_gasto->status === EstadoProcesoRendicionGasto::STATUS_VERIFICADO) {
                        $last_cuenta_bancaria = $proceso_rendicion_gasto->solicitud->funcionario->lastCuentaBancaria();
                        if (!$last_cuenta_bancaria) {
                            return response()->json([
                                'errors' =>  [$proceso_rendicion_gasto->solicitud->funcionario->abreNombres() . " no registra cuenta bancaria habilitada o algún medio de pago."]
                            ], 422);
                        }
                    }
                    $aprobar = $this->aprobarProcesoRendicion($proceso_rendicion_gasto, $observacion);
                    break;

                case 3:
                    $this->authorize('rechazar', $proceso_rendicion_gasto);
                    $this->rechazarProcesoRendicion($proceso_rendicion_gasto, $observacion);
                    break;
            }
            $proceso_rendicion_gasto = $proceso_rendicion_gasto->fresh();
            $navStatus  = $this->navStatusRendicion($proceso_rendicion_gasto);
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => 'Rendición modificada con éxito.',
                    'message'       => null,
                    'data'          => ProcesoRendicionGastoResource::make($proceso_rendicion_gasto),
                    'nav'           => $navStatus
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    private function anularProcesoRendicion($proceso_rendicion_gasto, $observacion)
    {
        try {
            $firma_disponible = $this->isFirmaDisponibleProcesoRendicionActionPolicy($proceso_rendicion_gasto, 'rendicion.firma.anular');
            $estado = [
                'observacion'           => $observacion,
                'status'                => EstadoProcesoRendicionGasto::STATUS_ANULADO,
                'p_rendicion_gasto_id'  => $proceso_rendicion_gasto->id,
                'role_id'               => $firma_disponible->is_firma ? $firma_disponible->firma->role_id : null,
                'posicion_firma'        => $firma_disponible->is_firma ? $firma_disponible->firma->posicion_firma : null,
                'is_subrogante'         => $firma_disponible->is_subrogante
            ];
            $status = EstadoProcesoRendicionGasto::create($estado);

            $proceso_rendicion_gasto = $proceso_rendicion_gasto->fresh();

            $last_status = $proceso_rendicion_gasto->estados()->orderBy('id', 'DESC')->first();
            ProcesoRendicionGastoStatus::dispatch($last_status);
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    private function rechazarProcesoRendicion($proceso_rendicion_gasto, $observacion)
    {
        try {
            $firma_disponible = $this->obtenerFirmaDisponibleProcesoRendicion($proceso_rendicion_gasto, 'rendicion.firma.rechazar');
            $estado = [
                'observacion'           => $observacion,
                'status'                => EstadoProcesoRendicionGasto::STATUS_RECHAZADO,
                'p_rendicion_gasto_id'  => $proceso_rendicion_gasto->id,
                'role_id'               => $firma_disponible->is_firma ? $firma_disponible->role_id : null,
                'posicion_firma'        => $firma_disponible->is_firma ? $firma_disponible->posicion_firma : null,
                'is_subrogante'         => $firma_disponible->is_subrogante
            ];
            $status = EstadoProcesoRendicionGasto::create($estado);
            if ($status) {
                $proceso_rendicion_gasto->update([
                    'posicion_firma_ok' =>  0,
                    'fecha_last_firma'  => now()
                ]);
            }
            $rendiciones = $proceso_rendicion_gasto->rendiciones()
                ->where('rinde_gasto', true)
                ->where('last_status', '!=', RendicionGasto::STATUS_PENDIENTE)
                ->get();

            if (count($rendiciones) > 0) {
                foreach ($rendiciones as $rendicion) {
                    $rendicion->update([
                        'mount_real'    => $rendicion->mount,
                        'last_status'   => RendicionGasto::STATUS_PENDIENTE
                    ]);
                }
            }
            $proceso_rendicion_gasto = $proceso_rendicion_gasto->fresh();

            $last_status = $proceso_rendicion_gasto->estados()->orderBy('id', 'DESC')->first();
            ProcesoRendicionGastoStatus::dispatch($last_status);
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    public function aprobarProcesoRendicion($proceso_rendicion_gasto, $observacion)
    {
        try {
            if ($proceso_rendicion_gasto->status === EstadoProcesoRendicionGasto::STATUS_VERIFICADO) {
                $status = EstadoProcesoRendicionGasto::STATUS_APROBADO_N;
                if ($proceso_rendicion_gasto->isRendicionesModificadas()) {
                    $status = EstadoProcesoRendicionGasto::STATUS_APROBADO_S;
                }
                $last_cuenta_bancaria = $proceso_rendicion_gasto->solicitud->funcionario->lastCuentaBancaria();

                if ($last_cuenta_bancaria) {
                    $proceso_rendicion_gasto->update([
                        'cuenta_bancaria_id'    => $last_cuenta_bancaria->id
                    ]);
                }
            } else if ($proceso_rendicion_gasto->status === EstadoProcesoRendicionGasto::STATUS_INGRESADA || $proceso_rendicion_gasto->status === EstadoProcesoRendicionGasto::STATUS_MODIFICADA) {
                $status = EstadoProcesoRendicionGasto::STATUS_APROBADO_JD;
            }

            $firma_disponible = $this->obtenerFirmaDisponibleProcesoRendicion($proceso_rendicion_gasto, 'rendicion.firma.validar');
            $next_firma_roceso_rendicion = $this->nextFirmaProcesoRendicion('>', $proceso_rendicion_gasto->solicitud, $firma_disponible);
            $estado = [
                'status'                => $status,
                'observacion'           => $observacion,
                'p_rendicion_gasto_id'  => $proceso_rendicion_gasto->id,
                'role_id'               => $firma_disponible->is_firma ? $firma_disponible->role_id : null,
                'posicion_firma'        => $firma_disponible->is_firma ? $firma_disponible->posicion_firma : null,
                'is_subrogante'         => $firma_disponible->is_subrogante
            ];
            if ($next_firma_roceso_rendicion) {
                $proceso_rendicion_gasto->update([
                    'posicion_firma_ok' =>  $next_firma_roceso_rendicion->posicion_firma,
                    'fecha_last_firma'  => now()
                ]);
            }

            $status_r = EstadoProcesoRendicionGasto::create($estado);

            $proceso_rendicion_gasto = $proceso_rendicion_gasto->fresh();

            $last_status = $proceso_rendicion_gasto->estados()->orderBy('id', 'DESC')->first();
            ProcesoRendicionGastoStatus::dispatch($last_status);
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    public function statusRendicion(StatusRendicionRequest $request, $uuid)
    {
        try {
            $rendicion      = RendicionGasto::where('uuid', $uuid)->firstOrFail();
            $this->authorize('update', $rendicion);
            $rendicion_old  = $rendicion->replicate();
            $status         = (int)$request->status;
            $message_status = EstadoRendicionGasto::STATUS_DESC[$status];

            if ($rendicion) {
                $mount_real = (int)$request->mount_real;
                $update     = $rendicion->update([
                    'last_status'   => $status,
                    'mount_real'    => $status === 0 ? $rendicion->mount : $mount_real
                ]);

                if ($update) {
                    $firma_disponible = $this->isFirmaDisponibleProcesoRendicionActionPolicy($rendicion->procesoRendicionGasto, 'rendicion.actividad.validar');
                    $count_rendiciones              = $rendicion->procesoRendicionGasto->rendiciones()->where('rinde_gasto', true)->count();
                    $count_rendiciones_aprobadas    = $rendicion->procesoRendicionGasto->rendiciones()->where('rinde_gasto', true)->where('last_status', '!=', EstadoRendicionGasto::STATUS_PENDIENTE)->count();
                    if ($count_rendiciones_aprobadas >= $count_rendiciones) {
                        $estado = [
                            'status'                => EstadoProcesoRendicionGasto::STATUS_VERIFICADO,
                            'p_rendicion_gasto_id'  => $rendicion->procesoRendicionGasto->id,
                            'role_id'               => $firma_disponible->is_firma ? $firma_disponible->firma->role_id : null,
                            'posicion_firma'        => $firma_disponible->is_firma ? $firma_disponible->firma->posicion_firma : null,
                            'is_subrogante'         => $firma_disponible->is_subrogante
                        ];
                        $status_r = EstadoProcesoRendicionGasto::create($estado);

                        $before_or_after = $firma_disponible->is_firma ? ($firma_disponible->firma->role_id === 6 ? '>' : '>=') : null;
                        if ($status_r) {
                            $next_firma_roceso_rendicion = $this->nextFirmaProcesoRendicion($before_or_after, $rendicion->procesoRendicionGasto->solicitud, $firma_disponible);
                            if ($next_firma_roceso_rendicion) {
                                $rendicion->procesoRendicionGasto->update([
                                    'posicion_firma_ok' =>  $next_firma_roceso_rendicion->posicion_firma,
                                    'fecha_last_firma'  => now()
                                ]);
                            }
                        }
                    } else {
                        $total_en_proceso = $rendicion->procesoRendicionGasto->estados()->where('status', EstadoProcesoRendicionGasto::STATUS_EN_PROCESO)->count();

                        if ($total_en_proceso <= 0) {
                            $estado = [
                                'status'                => EstadoProcesoRendicionGasto::STATUS_EN_PROCESO,
                                'p_rendicion_gasto_id'  => $rendicion->procesoRendicionGasto->id,
                                'role_id'               => $firma_disponible->is_firma ? $firma_disponible->firma->role_id : null,
                                'posicion_firma'        => $firma_disponible->is_firma ? $firma_disponible->firma->posicion_firma : null,
                                'is_subrogante'         => $firma_disponible->is_subrogante
                            ];
                            $status_r = EstadoProcesoRendicionGasto::create($estado);
                        }
                    }
                    $rendicion  = $rendicion->fresh();
                    $data[]     = [
                        'status'          => $status,
                        'observacion'     => $request->observacion,
                        'mount_real'      => $mount_real,
                        'rendicion_old'   => $rendicion_old,
                        'rendicion_new'   => $rendicion
                    ];

                    $rendicion->addStatus($data);
                    $status_proceso_rendicion = EstadoProcesoRendicionGasto::STATUS_NOM[$rendicion->procesoRendicionGasto->status];
                    $navStatus  = $this->navStatusRendicion($rendicion->procesoRendicionGasto);
                    return response()->json(
                        array(
                            'status'  => 'success',
                            'title'   => "Rendición {$message_status} con éxito",
                            'message' => "Proceso de rendición $status_proceso_rendicion",
                            'data'    => ProcesoRendicionGastoDetalleResource::make($rendicion->procesoRendicionGasto),
                            'nav'     => $navStatus
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function feriadosFecha($fecha)
    {
        $fecha      = Carbon::parse($fecha);
        $anio       = $fecha->format('Y');
        $cacheKey   = "feriados_{$anio}";
        $feriados   = Cache::get($cacheKey);
        if ($feriados !== null) {
            return $feriados;
        }

        try {
            $url        = "https://apis.digital.gob.cl/fl/feriados/{$anio}";
            $response   = Http::get($url);
            if ($response->successful()) {
                $apiResponse = $response->body();
                $feriados = json_decode($apiResponse, true, 512, JSON_UNESCAPED_UNICODE);

                if (is_array($feriados)) {
                    $fechas = collect($feriados)->pluck('fecha')->toArray();
                    Cache::put($cacheKey, $fechas, now()->addDays(31));
                    return $fechas;
                }
            }
            return [];
        } catch (\Exception $exception) {
            Log::error("Error al procesar la solicitud de feriados: {$exception->getMessage()}");
            $feriados = Cache::get($cacheKey);
            return $feriados !== null ? $feriados : [];
        }
    }

    public function updatePago(Request $request)
    {
        try {
            $proceso_rendicion_gasto = ProcesoRendicionGasto::where('uuid', $request->uuid)->firstOrFail();

            if ($proceso_rendicion_gasto) {
                $fecha_pago = null;
                $dias_habiles_pago = $request->dias_habiles_pago != null ? (int)$request->dias_habiles_pago : NULL;
                if ($dias_habiles_pago !== NULL) {
                    $estado_ok = $proceso_rendicion_gasto->estados()->whereIn('status', [EstadoProcesoRendicionGasto::STATUS_APROBADO_N, EstadoProcesoRendicionGasto::STATUS_APROBADO_S])->orderBy('id', 'DESC')->first();
                    if ($estado_ok) {
                        $inicio             = Carbon::parse($estado_ok->fecha_by_user)->addDay(1);
                        $fecha_final        = $inicio->copy();
                        $diasAgregados      = 0;
                        $feriados_anio      = $this->feriadosFecha($inicio);

                        while ($diasAgregados < $dias_habiles_pago) {
                            $fecha_final->addDay();

                            if ($fecha_final->isWeekday() && !in_array($fecha_final->format('Y-m-d'), $feriados_anio)) {
                                $diasAgregados++;
                            }
                        }
                        $fecha_pago = $fecha_final;
                    }
                }
                $update = $proceso_rendicion_gasto->update([
                    'dias_habiles_pago'     => $dias_habiles_pago,
                    'fecha_pago'            => $fecha_pago
                ]);

                if ($update) {
                    $proceso_rendicion_gasto = $proceso_rendicion_gasto->fresh();
                    $navStatus  = $this->navStatusRendicion($proceso_rendicion_gasto);
                    return response()->json(
                        array(
                            'status'  => 'success',
                            'title'   => "Rendición $proceso_rendicion_gasto->n_folio.",
                            'message' => "Días de pago modificado con éxito",
                            'data'    => ProcesoRendicionGastoDetalleResource::make($proceso_rendicion_gasto),
                            'nav'     => $navStatus
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
