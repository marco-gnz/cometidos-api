<?php

namespace App\Http\Controllers\Admin\Reasignacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reasignacion\StoreReasignacionRequest;
use App\Http\Resources\Reasignacion\ListReasignacionResource;
use App\Models\Reasignacion;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Http\Request;

class ReasignacionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function listReasignaciones()
    {
        try {
            $this->authorize('viewAny', Reasignacion::class);
            $reasignaciones = Reasignacion::orderBy('id', 'DESC')->get();

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => ListReasignacionResource::collection($reasignaciones)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function storeReasignacion(StoreReasignacionRequest $request)
    {
        try {
            $this->authorize('create', Reasignacion::class);
            $form = [
                'firmante_uuid',
                'fecha_inicio',
                'fecha_termino',
                'subrogante_uuid'
            ];

            $firmante   = User::where('uuid', $request->firmante_uuid)->firstOrFail();
            $subrogante = User::where('uuid', $request->subrogante_uuid)->firstOrFail();

            $data = [
                'fecha_inicio'          => $request->fecha_inicio,
                'fecha_termino'         => $request->fecha_inicio,
                'user_ausente_id'       => $firmante->id,
                'user_subrogante_id'    => $subrogante->id
            ];
            $reasignacion = Reasignacion::create($data);

            if ($reasignacion) {
                $reasignacion->solicitudes()->attach($request->solicitudes_id);
                $reasignacion = $reasignacion->fresh();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Reasignación ingresada con éxito",
                        'message'       => null,
                        'data'          => ListReasignacionResource::make($reasignacion)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }

    public function deleteReasignacion($uuid)
    {
        try {
            $reasignacion   = Reasignacion::where('uuid', $uuid)->firstOrFail();
            $this->authorize('delete', $reasignacion);
            $delete         = $reasignacion->solicitudes()->detach();
            $delete         = $reasignacion->delete();
            if ($delete) {
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => 'Reasignación eliminada con éxito.',
                        'message'       => null
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json($error->getMessage());
        }
    }
}
