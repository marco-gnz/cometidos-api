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
            $fecha_inicio   = Carbon::parse($request->fecha_inicio)->startOfDay();
            $fecha_termino  = Carbon::parse($request->fecha_termino)->endOfDay();

            $count_solicitudes_anterior = Solicitud::where('fecha_by_user', '<', $fecha_inicio)
                ->whereHas('firmantes', function ($q) use ($firmante) {
                    $q->where('user_id', $firmante->id)
                        ->where('status', true)
                        ->where('is_executed', false);
                })->count();

            $count_solicitudes_rango = Solicitud::whereBetween('fecha_by_user', [$fecha_inicio, $fecha_termino])
                ->whereHas('firmantes', function ($q) use ($firmante) {
                    $q->where('user_id', $firmante->id)
                        ->where('status', true)
                        ->whereIn('is_executed', [true, false]);
                })->get();

            $count_solicitudes_posterior = Solicitud::where('fecha_by_user', '>', $fecha_termino)
                ->whereHas('firmantes', function ($q) use ($firmante) {
                    $q->where('user_id', $firmante->id)
                        ->where('status', true)
                        ->where('is_executed', false);
                })->count();

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
            $firmante = User::where('uuid', $request->firmante_uuid)->firstOrFail();
            $data = [
                'user_ausente_id'   => $firmante->id,
                'fecha_inicio'      => $request->fecha_inicio,
                'fecha_termino'     => $request->fecha_termino
            ];

            $validateExistAusentismoUser = $this->validateExistAusentismoUser($request, $firmante);
            $validateExistAusentismoFirmantes = $this->validateExistAusentismoFirmantes($request);

            if (!$validateExistAusentismoUser) {
                return response()->json([
                    'errors' => [
                        'fecha_inicio'  => ['Funcionario ausente registras otro ausentismo en el periodo de ausentismo.'],
                    ]
                ], 422);
            }

            if (!$validateExistAusentismoFirmantes) {
                return response()->json([
                    'errors' => [
                        'subrogantes_id'  => ['Un firmante seleccionado ya registras otro ausentismo en el periodo de ausentismo.'],
                    ]
                ], 422);
            }

            $ausentismo = Ausentismo::create($data);

            if ($ausentismo) {
                $ausentismo->subrogantes()->attach($request->subrogantes_id);

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
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function deleteAusentismo($uuid)
    {
        try {
            $ausentismo = Ausentismo::where('uuid', $uuid)->firstOrFail();
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

    private function validateExistAusentismoFirmantes($request)
    {
        $users_id           = User::where('id', $request->subrogantes_id)->pluck('id')->toArray();
        $fecha_inicio       = $request->fecha_inicio;
        $fecha_termino      = $request->fecha_termino;
        if (count($users_id) > 0) {
            foreach ($users_id as $id) {
                $total = Ausentismo::where('user_ausente_id', $id)
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
            }
        }
        return true;
    }
}
