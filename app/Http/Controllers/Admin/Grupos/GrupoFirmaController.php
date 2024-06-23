<?php

namespace App\Http\Controllers\Admin\Grupos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Grupo\StoreFirmanteRequest;
use App\Http\Requests\Grupo\StoreGrupoRequest;
use App\Http\Resources\Grupo\GrupoResource;
use App\Http\Resources\Grupo\ListGrupoResource;
use App\Models\Firmante;
use App\Models\Grupo;
use App\Models\User;
use Illuminate\Http\Request;

class GrupoFirmaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function listGruposFirma(Request $request)
    {
        try {
            $this->authorize('viewAny', Grupo::class);
            $grupos = Grupo::searchInput($request->input)
                ->searchEstablecimiento($request->establecimientos_id)
                ->searchDepto($request->deptos_id)
                ->searchSubdepto($request->subdeptos_id)
                ->searchPerfil($request->perfiles_id)
                ->orderByRaw('CAST(codigo AS UNSIGNED) ASC')
                ->paginate(50);

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'pagination' => [
                        'total'         => $grupos->total(),
                        'total_desc'    => $grupos->total() > 1 ? "{$grupos->total()} resultados" : "{$grupos->total()} resultado",
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
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function findGrupoFirma($uuid)
    {
        try {
            $grupo = Grupo::where('uuid', $uuid)->firstOrFail();
            $this->authorize('view', $grupo);

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => GrupoResource::make($grupo)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function deleteGrupo($uuid)
    {
        try {
            $grupo = Grupo::where('uuid', $uuid)->firstOrFail();
            $this->authorize('delete', $grupo);
            $total_solicitudes = $grupo->solicitudes()->count();
            if ($total_solicitudes > 0) {
                return response()->json(['error' => 'No es posible eliminar grupo.'], 500);
            }

            $delete = $grupo->delete();
            if ($delete) {
                $this->resetGrupos();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Grupo eliminado con éxito.",
                        'message'       => null,
                        'data'          => null
                    )
                );
            }
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => "Grupo eliminado con éxito.",
                    'message'       => null,
                    'data'          => null
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    private function resetGrupos()
    {
        try {
            $grupos = Grupo::all();
            $codigo_incremental = 1;
            $codigo_asignado = [];

            foreach ($grupos as $key => $grupo) {
                // Genera el código inicial
                $codigo_base = (string) $codigo_incremental;
                $codigo = $codigo_base;

                // Verifica si el grupo ya tiene un código asignado
                if (isset($codigo_asignado[$grupo->id])) {
                    // Si ya tiene un código asignado, úsalo
                    $codigo = $codigo_asignado[$grupo->id];
                } else {
                    // Busca duplicados con el mismo establecimiento_id, departamento_id y sub_departamento_id
                    $duplicados = Grupo::where('establecimiento_id', $grupo->establecimiento_id)
                        ->where('departamento_id', $grupo->departamento_id)
                        ->where('sub_departamento_id', $grupo->sub_departamento_id)
                        ->orderBy('id', 'asc')
                        ->get();

                    // Si hay duplicados, asignar el código con sufijos
                    if ($duplicados->isNotEmpty()) {
                        $max_sufijo = 0;
                        foreach ($duplicados as $duplicado) {
                            if ($duplicado->id != $grupo->id) {
                                $codigo_asignado[$duplicado->id] = $codigo_base . '_' . ++$max_sufijo;
                            } else {
                                $codigo_asignado[$grupo->id] = $codigo_base;
                            }
                        }
                    } else {
                        $codigo_asignado[$grupo->id] = $codigo_base;
                    }
                }

                // Actualiza el registro con el nuevo código
                $grupo->codigo = $codigo_asignado[$grupo->id];
                $grupo->save();

                // Incrementa el código base para el próximo grupo
                $codigo_incremental++;
            }
        } catch (\Exception $error) {
            Log::info($error->getMessage());
            return $error->getMessage();
        }
    }

    public function changePosition(Request $request)
    {
        try {
            $firma = Firmante::where('uuid', $request->firmante_uuid)->firstOrFail();
            $this->authorize('update', $firma->grupo);
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
                            'data'          => GrupoResource::make($grupo)
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
            $this->authorize('update', $grupo);
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
                'data'      => GrupoResource::make($grupo)
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
                'data'      => GrupoResource::make($grupo)
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
            $this->authorize('update', $grupo);
            $firma->delete();

            $firmantes = $grupo->firmantes()->orderBy('posicion_firma')->get();
            $firmantes->each(function ($firmante, $index) {
                $firmante->update(['posicion_firma' => $index + 1]);
            });

            $grupo = $grupo->fresh();
            return response()->json([
                'status'    => 'success',
                'title'     => 'Firmante eliminado con éxito',
                'data'      => GrupoResource::make($grupo)
            ]);
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function storeGrupo(StoreGrupoRequest $request)
    {
        try {
            $this->authorize('create', Grupo::class);
            $form = ['establecimiento_id', 'departamento_id', 'sub_departamento_id'];

            $validate_duplicate_grupo_firmantes = $this->validateDuplicateGrupoFirmantes($request);
            $firmantes          = [];
            if ($validate_duplicate_grupo_firmantes) {
                $message = "Ya existe un grupo de firma asignado al establecimiento, depto. y subdepto y uno o más firmantes se repiten.";
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

    private function validateDuplicateGrupoFirmantes($request)
    {
        $firmantes = [];
        if ($request->firmantes) {
            foreach ($request->firmantes as $key => $firmante) {
                $firmante_id        = (int)$firmante['id'];
                $role_id            = (int)$firmante['role_id'];
                array_push($firmantes, $firmante_id);
            }
        }

        $existe = false;

        $grupos = Grupo::where('establecimiento_id', $request->establecimiento_id)
            ->where('departamento_id', $request->departamento_id)
            ->where('sub_departamento_id', $request->sub_departamento_id)
            ->with('firmantes')
            ->get();

        foreach ($grupos as $grupo) {
            $firmantesGrupo = $grupo->firmantes->pluck('user_id')->toArray();

            sort($firmantes);
            sort($firmantesGrupo);

            if ($firmantes == $firmantesGrupo) {
                $existe = true;
                break;
            }
        }


        return $existe;
    }
}
