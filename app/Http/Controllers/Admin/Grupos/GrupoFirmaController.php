<?php

namespace App\Http\Controllers\Admin\Grupos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Grupo\StoreGrupoRequest;
use App\Http\Resources\Grupo\ListGrupoResource;
use App\Models\Grupo;
use Illuminate\Http\Request;

class GrupoFirmaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function listGruposFirma()
    {
        try {
            $grupos = Grupo::orderBy('id', 'ASC')->get();

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
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
            return $error->getMessage();
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
