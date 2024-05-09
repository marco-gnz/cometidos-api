<?php

namespace App\Http\Controllers\Admin\Grupos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Grupo\StoreFirmanteRequest;
use App\Http\Requests\Grupo\StoreGrupoRequest;
use App\Http\Resources\Grupo\ListGrupoResource;
use App\Models\Firmante;
use App\Models\Grupo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GrupoFirmaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function listGruposFirma(Request $request)
    {
        try {
            $grupos = Grupo::searchInput($request->input)
                ->searchEstablecimiento($request->establecimientos_id)
                ->searchDepto($request->deptos_id)
                ->searchSubdepto($request->subdeptos_id)
                ->searchPerfil($request->perfiles_id)
                ->orderBy('id', 'DESC')
                ->paginate(50);

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'pagination' => [
                        'total'         => $grupos->total(),
                        'current_page'  => $grupos->currentPage(),
                        'per_page'      => $grupos->perPage(),
                        'last_page'     => $grupos->lastPage(),
                        'from'          => $grupos->firstItem(),
                        'to'            => $grupos->lastPage()
                    ],
                    'data'          => ListGrupoResource::collection($grupos)
                )
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function findGrupoFirma($uuid)
    {
        try {
            $grupo = Grupo::where('uuid', $uuid)->firstOrFail();

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => ListGrupoResource::make($grupo)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function changePosition(Request $request)
    {
        try {
            $firma = Firmante::where('uuid', $request->firmante_uuid)->firstOrFail();
            if ($firma) {
                $posicion_actual        = $firma->posicion_firma;
                $nuevo_valor_posicion   = ($request->up_down == 'sum') ? ($posicion_actual + 1) : ($posicion_actual - 1);
                $nuevo_valor_posicion   = max(1, $nuevo_valor_posicion);

                $firmaAfectada = $firma->grupo->firmantes()->where('posicion_firma', $nuevo_valor_posicion)->first();
                if ($firmaAfectada) {
                    if ($request->up_down == 'sum') {
                        $firmaAfectada->update([
                            'posicion_firma'    => $firmaAfectada->posicion_firma - 1
                        ]);
                    } else {
                        $firmaAfectada->update([
                            'posicion_firma'    => $firmaAfectada->posicion_firma + 1
                        ]);
                    }
                }
                $update = $firma->update([
                    'posicion_firma'    => $nuevo_valor_posicion
                ]);
                if ($update) {
                    $grupo = $firma->grupo->fresh();
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => 'Posición modificada con éxito',
                            'message'       => null,
                            'data'          => ListGrupoResource::make($grupo)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
    public function storeFirmanteGrupo(StoreFirmanteRequest $request)
    {
        try {
            $grupo              = Grupo::where('uuid', $request->grupo_uuid)->firstOrFail();
            $funcinario         = User::find($request->funcionario_id);
            $perfil_id          = $request->perfil_id;
            $total_firmantes    = $grupo->firmantes()->count();

            $firmantes[] = [
                'posicion_firma'    => $total_firmantes + 1,
                'user_id'           => $funcinario->id,
                'role_id'           => $perfil_id
            ];

            $grupo->addFirmantes($firmantes);

            $grupo = $grupo->fresh();

            return response()->json([
                'status'    => 'success',
                'title'     => 'Firmante agregado con éxito',
                'data'      => ListGrupoResource::make($grupo)
            ]);
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function getUsersNotGrupo(Request $request)
    {
        try {
            $grupo = Grupo::where('uuid', $request->grupo_uuid)->firstOrFail();

            $firmantes = $grupo->firmantes()->get();
            return response()->json([
                'status'    => 'success',
                'title'     => 'Firmante eliminado con éxito',
                'data'      => ListGrupoResource::make($grupo)
            ]);
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function deleteFirmante($uuid)
    {
        try {
            $firma = Firmante::where('uuid', $uuid)->firstOrFail();
            $grupo = $firma->grupo;

            $firma->delete();

            $firmantes = $grupo->firmantes()->orderBy('posicion_firma')->get();
            $firmantes->each(function ($firmante, $index) {
                $firmante->update(['posicion_firma' => $index + 1]);
            });

            $grupo = $grupo->fresh();
            return response()->json([
                'status'    => 'success',
                'title'     => 'Firmante eliminado con éxito',
                'data'      => ListGrupoResource::make($grupo)
            ]);
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function storeGrupo(StoreGrupoRequest $request)
    {
        try {
            $form = ['establecimiento_id', 'departamento_id', 'sub_departamento_id'];

            $validate_duplicate = $this->validateDuplicate($request);
            $firmantes          = [];

            if ($validate_duplicate) {
                $message = "Ya existe un grupo con los mismos datos.";
                return response()->json([
                    'errors' => [
                        'establecimiento_id'    => [$message],
                        'departamento_id'       => [$message],
                        'sub_departamento_id'   => [$message]
                    ]
                ], 422);
            }
            $grupo = Grupo::create($request->only($form));

            if ($grupo) {
                if ($request->firmantes) {
                    foreach ($request->firmantes as $key => $firmante) {
                        $firmante_id        = (int)$firmante['id'];
                        $role_id            = (int)$firmante['role_id'];
                        $posicion_firma     = $key + 1;

                        $firmantes[] = [
                            'posicion_firma'    => $posicion_firma,
                            'grupo_id'          => $grupo->id,
                            'user_id'           => $firmante_id,
                            'role_id'           => $role_id
                        ];
                    }
                    $grupo->addFirmantes($firmantes);
                }

                $grupo = $grupo->fresh();

                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Grupo de firmantes ingresado con éxito.",
                        'message'       => null,
                        'data'          => ListGrupoResource::make($grupo)
                    )
                );
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    private function validateDuplicate($request)
    {
        $existe = false;

        $count = Grupo::where('establecimiento_id', $request->establecimiento_id)
            ->where('departamento_id', $request->departamento_id)
            ->where('sub_departamento_id', $request->sub_departamento_id)
            ->count();

        if ($count > 0) {
            $existe = true;
        }
        return $existe;
    }
}
