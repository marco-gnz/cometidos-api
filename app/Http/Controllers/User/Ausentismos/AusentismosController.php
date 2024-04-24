<?php

namespace App\Http\Controllers\User\Ausentismos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ausentismo\StoreAusentismoRequest;
use App\Http\Resources\User\Ausentismo\ListAusentismoResource;
use App\Models\Ausentismo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AusentismosController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function listAusentismos()
    {
        try {
            $ausentismos = Ausentismo::where('user_ausente_id', auth()->user()->id)->get();

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

    public function storeAusentismo(StoreAusentismoRequest $request)
    {
        try {
            $data = [
                'fecha_inicio'      => $request->fecha_inicio,
                'fecha_termino'     => $request->fecha_termino,
                'user_ausente_id'   => auth()->user()->id
            ];

            $validateExistAusentismoUser = $this->validateExistAusentismoUser($request);
            $validateExistAusentismoFirmantes = $this->validateExistAusentismoFirmantes($request);

            if (!$validateExistAusentismoUser) {
                return response()->json([
                    'errors' => [
                        'fecha_inicio'  => ['Ya registras otro ausentismo en el periodo de ausentismo.'],
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

    private function validateExistAusentismoUser($request)
    {
        $auth               = auth()->user();
        $fecha_inicio       = $request->fecha_inicio;
        $fecha_termino      = $request->fecha_termino;

        $total = Ausentismo::where('user_ausente_id', $auth->id)
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
        $users_id           = User::whereIn('id', $request->subrogantes_id)->pluck('id')->toArray();
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
