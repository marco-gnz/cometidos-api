<?php

namespace App\Http\Controllers\Admin\Ausentismo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ausentismo\StoreAdminAusentismoRequest;
use App\Http\Resources\Solicitud\ListSolicitudReasignarResource;
use App\Http\Resources\User\Ausentismo\ListAusentismoResource;
use App\Models\Ausentismo;
use App\Models\Solicitud;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AusentismoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getSolicitudesDate(Request $request)
    {
        try {
            $firmante       = User::where('uuid', $request->firmante_uuid)->firstOrFail();
            $subrogante     = User::where('uuid', $request->subrogante_uuid)->firstOrFail();
            $fecha_inicio   = Carbon::parse($request->fecha_inicio)->startOfDay();
            $fecha_termino  = Carbon::parse($request->fecha_termino)->endOfDay();
            $solicitudes_pendientes = $request->solicitudes_pendientes === 'true' ? true : false;
            $campo_search = $solicitudes_pendientes === true ? 'fecha_last_firma' : 'fecha_by_user';

            $count_solicitudes_anterior = Solicitud::where($campo_search, '<', $fecha_inicio)
                ->whereHas('firmantes', function ($q) use ($firmante, $solicitudes_pendientes, $subrogante, $fecha_inicio, $fecha_termino) {
                    $q->where('user_id', $firmante->id)
                        ->where('status', true)
                        ->where('is_executed', false)
                        ->where(function ($q) use ($firmante, $subrogante, $fecha_inicio, $fecha_termino) {
                            $q->whereDoesntHave('funcionario.ausentismos', function ($q) use ($firmante, $subrogante, $fecha_inicio, $fecha_termino) {
                                $q->where('user_ausente_id', $firmante->id)
                                    ->whereBetween('fecha_inicio', [$fecha_inicio->format('Y-m-d'), $fecha_termino->format('Y-m-d')])
                                    ->whereHas('subrogantes', function ($q) use ($subrogante) {
                                        $q->where('ausentismo_user.user_id', $subrogante->id);
                                    });
                            });
                        });
                    if ($solicitudes_pendientes) {
                        $q->whereRaw('solicituds.posicion_firma_ok = solicitud_firmantes.posicion_firma');
                    }
                })->whereDoesntHave('reasignaciones', function ($q) use ($subrogante) {
                    $q->where('user_subrogante_id', $subrogante->id);
                })
                ->count();

            $count_solicitudes_rango = Solicitud::whereBetween($campo_search, [$fecha_inicio, $fecha_termino])
                ->whereHas('firmantes', function ($q) use ($firmante, $solicitudes_pendientes, $subrogante, $fecha_inicio, $fecha_termino) {
                    $q->where('user_id', $firmante->id)
                        ->where('status', true)
                        ->whereIn('is_executed', [true, false])
                        ->where(function ($q) use ($firmante, $subrogante, $fecha_inicio, $fecha_termino) {
                            $q->whereDoesntHave('funcionario.ausentismos', function ($q) use ($firmante, $subrogante, $fecha_inicio, $fecha_termino) {
                                $q->where('user_ausente_id', $firmante->id)
                                    ->whereBetween('fecha_inicio', [$fecha_inicio->format('Y-m-d'), $fecha_termino->format('Y-m-d')])
                                    ->whereHas('subrogantes', function ($q) use ($subrogante) {
                                        $q->where('ausentismo_user.user_id', $subrogante->id);
                                    });
                            });
                        });
                    if ($solicitudes_pendientes) {
                        $q->whereRaw('solicituds.posicion_firma_ok = solicitud_firmantes.posicion_firma');
                    }
                })->whereDoesntHave('reasignaciones', function ($q) use ($subrogante) {
                    $q->where('user_subrogante_id', $subrogante->id);
                })
                ->get();

            $count_solicitudes_posterior = Solicitud::where($campo_search, '>', $fecha_termino)
                ->whereHas('firmantes', function ($q) use ($firmante, $solicitudes_pendientes, $subrogante, $fecha_inicio, $fecha_termino) {
                    $q->where('user_id', $firmante->id)
                        ->where('status', true)
                        ->where('is_executed', false)
                        ->where(function ($q) use ($firmante, $subrogante, $fecha_inicio, $fecha_termino) {
                            $q->whereDoesntHave('funcionario.ausentismos', function ($q) use ($firmante, $subrogante, $fecha_inicio, $fecha_termino) {
                                $q->where('user_ausente_id', $firmante->id)
                                    ->whereBetween('fecha_inicio', [$fecha_inicio->format('Y-m-d'), $fecha_termino->format('Y-m-d')])
                                    ->whereHas('subrogantes', function ($q) use ($subrogante) {
                                        $q->where('ausentismo_user.user_id', $subrogante->id);
                                    });
                            });
                        });
                    if ($solicitudes_pendientes) {
                        $q->whereRaw('solicituds.posicion_firma_ok = solicitud_firmantes.posicion_firma');
                    }
                })->whereDoesntHave('reasignaciones', function ($q) use ($subrogante) {
                    $q->where('user_subrogante_id', $subrogante->id);
                })
                ->count();

            $totales = (object)[
                'count_solicitudes_anterior'    => $count_solicitudes_anterior,
                'count_solicitudes_rango'       => count($count_solicitudes_rango),
                'count_solicitudes_posterior'   => $count_solicitudes_posterior,
                'solicitudes_a_reasignar'       => ListSolicitudReasignarResource::collection($count_solicitudes_rango)
            ];

            return response()->json([
                'status'                => 'success',
                'title'                 => null,
                'message'               => null,
                'totalesDate'           => $totales
            ]);
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }


    public function listAusentismos()
    {
        try {
            $this->authorize('viewAny', Ausentismo::class);
            $ausentismos = Ausentismo::orderBy('fecha_inicio', 'DESC')->get();

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => ListAusentismoResource::collection($ausentismos)
                )
            );
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function storeAusentismo(StoreAdminAusentismoRequest $request)
    {
        try {
            $this->authorize('create', Ausentismo::class);
            $firmante = User::where('uuid', $request->firmante_uuid)->firstOrFail();
            $subrogante = User::where('uuid', $request->subrogante_uuid)->firstOrFail();
            $data = [
                'user_ausente_id'   => $firmante->id,
                'fecha_inicio'      => $request->fecha_inicio,
                'fecha_termino'     => $request->fecha_termino
            ];

            $validateExistAusentismoUser = $this->validateExistAusentismoUser($request, $firmante);
            $validateExistAusentismoFirmantes = $this->validateExistAusentismoFirmantes($request, $subrogante);

            if (!$validateExistAusentismoUser) {
                return response()->json([
                    'errors' => [
                        'fecha_inicio'  => ['Funcionario ausente registras otro ausentismo en el periodo de ausentismo.'],
                    ]
                ], 422);
            }

            /* if (!$validateExistAusentismoFirmantes) {
                return response()->json([
                    'errors' => [
                        'subrogante_uuid'  => ['Subrogante seleccionado ya registra un ausentismo en el periodo de ausentismo.'],
                    ]
                ], 422);
            } */

            if ($subrogante) {
                $ausentismo = Ausentismo::create($data);

                if ($ausentismo) {
                    $ausentismo->subrogantes()->attach([$subrogante->id]);

                    if ($ausentismo) {
                        return response()->json(
                            array(
                                'status'        => 'success',
                                'title'         => 'Ausentismo ingresado con éxito.',
                                'message'       => null,
                                'data'          => ListAusentismoResource::make($ausentismo)
                            )
                        );
                    }
                }
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function deleteAusentismo($uuid)
    {
        try {
            $ausentismo = Ausentismo::where('uuid', $uuid)->firstOrFail();
            $this->authorize('delete', $ausentismo);
            $delete = $ausentismo->subrogantes()->detach();
            $delete = $ausentismo->delete();
            if ($delete) {
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => 'Ausentismo eliminado con éxito.',
                        'message'       => null
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    private function validateExistAusentismoUser($request, $firmante)
    {
        $fecha_inicio       = $request->fecha_inicio;
        $fecha_termino      = $request->fecha_termino;

        $total = Ausentismo::where('user_ausente_id', $firmante->id)
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
            ->count();

        if ($total > 0) {
            return false;
        }
        return true;
    }

    private function validateExistAusentismoFirmantes($request, $subrogante)
    {
        $fecha_inicio       = $request->fecha_inicio;
        $fecha_termino      = $request->fecha_termino;
        $total = Ausentismo::where(function ($q) use ($subrogante) {
            $q->whereHas('subrogantes', function ($q) use ($subrogante) {
                $q->where('ausentismo_user.user_id', $subrogante->id);
            });
        })
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
            ->count();
        if ($total > 0) {
            return false;
        }
        return true;
    }
}
