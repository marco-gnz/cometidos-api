<?php

namespace App\Http\Controllers\Admin\Conceptos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Concepto\StoreConceptoEstablecimientoRequest;
use App\Http\Resources\Concepto\ListConceptoResource;
use App\Models\Concepto;
use App\Models\ConceptoEstablecimiento;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConceptosController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getUsersConcepto(Request $request)
    {
        try {
            $this->authorize('viewAny', Concepto::class);
            $conceptoEstablecimiento = ConceptoEstablecimiento::where('id', $request->concepto_establecimiento_id)
                ->firstOrFail();

            $users_id   = $conceptoEstablecimiento->funcionarios()->pluck('users.id')->toArray();
            $new_users  = User::general($request->input)->whereNotIn('id', $users_id)->get();
            return response()->json([
                'status'  => 'success',
                'title'   => null,
                'message' => null,
                'data'    => $new_users
            ]);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function listConceptos(Request $request)
    {
        try {
            $this->authorize('viewAny', Concepto::class);
            $establecimiento_id = $request->establecimiento_id;
            $conceptos          = Concepto::with(['conceptosEstablecimientos' => function ($q) use ($establecimiento_id) {
                $q->where('establecimiento_id', $establecimiento_id);
            }, 'conceptosEstablecimientos.funcionarios'])
                ->orderBy('nombre', 'ASC')
                ->get();

            return response()->json([
                'status'  => 'success',
                'title'   => null,
                'message' => null,
                'data'    => ListConceptoResource::collection($conceptos)
            ]);
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function changePosition(Request $request)
    {
        try {
            $position_actual = $request->position_actual;
            $conceptoEstablecimiento = ConceptoEstablecimiento::where('id', $request->concepto_establecimiento_id)
                ->with(['funcionarios' => function ($q) use ($position_actual) {
                    $q->where('concepto_establecimiento_user.posicion', $position_actual);
                }])
                ->firstOrFail();
            $this->authorize('update', $conceptoEstablecimiento->concepto);

            $funcionario = $conceptoEstablecimiento->funcionarios->first();

            if ($funcionario) {
                $posicion_actual        = $funcionario->pivot->posicion;
                $nuevo_valor_posicion   = ($request->up_down == 'sum') ? ($posicion_actual + 1) : ($posicion_actual - 1);
                $nuevo_valor_posicion   = max(1, $nuevo_valor_posicion);

                $conceptoEstablecimientoAfectado = ConceptoEstablecimiento::where('id', $request->concepto_establecimiento_id)
                    ->with(['funcionarios' => function ($q) use ($nuevo_valor_posicion) {
                        $q->where('concepto_establecimiento_user.posicion', $nuevo_valor_posicion);
                    }])
                    ->firstOrFail();
                $funcionario_afectado = $conceptoEstablecimientoAfectado->funcionarios->first();

                if ($request->up_down == 'sum') {
                    $funcionario_afectado->pivot->posicion = $funcionario_afectado->pivot->posicion - 1;
                    $funcionario_afectado->pivot->save();
                } else {
                    $funcionario_afectado->pivot->posicion = $funcionario_afectado->pivot->posicion + 1;
                    $funcionario_afectado->pivot->save();
                }
                $funcionario->pivot->posicion = $nuevo_valor_posicion;
                $funcionario->pivot->save();
                $conceptoEstablecimiento = $conceptoEstablecimiento->fresh();
                return response()->json([
                    'status' => 'success',
                    'title' => 'Modificado con éxito.',
                    'message' => null,
                    'data' => $conceptoEstablecimiento->concepto
                ]);
            } else {
                return response()->json(['error' => 'No se encontró ningún funcionario con la posición actual especificada.'], 400);
            }
        } catch (\Exception $error) {
            return response()->json(['error' => 'Ha ocurrido un error al cambiar la posición.'], 500);
        }
    }

    public function storeUser(StoreConceptoEstablecimientoRequest $request)
    {
        try {
            $conceptoEstablecimiento = ConceptoEstablecimiento::where('id', $request->concepto_establecimiento_id)
                ->firstOrFail();
            $this->authorize('update', $conceptoEstablecimiento->concepto);
            $count_users = $conceptoEstablecimiento->funcionarios()->count();
            $user = User::where('uuid', $request->user_selected_id)->firstOrFail();
            $posicion = $count_users + 1;
            $role_id = null;

            if ($conceptoEstablecimiento->concepto->id === 1) {
                $role_id = 9;
            } else if ($conceptoEstablecimiento->concepto->id === 2) {
                $role_id = $request->role_id ? $request->role_id : null;
            }
            $conceptoEstablecimiento->funcionarios()->attach($user->id, ['posicion' => $posicion, 'role_id' => $role_id]);

            return response()->json([
                'status'    => 'success',
                'title'     => 'Usuario ingreado con éxito.',
                'message'   => null
            ]);
        } catch (\Exception $error) {
            return response()->json(['error' => 'Ha ocurrido un error al guardar el usuario.'], 500);
        }
    }

    public function deleteUser(Request $request)
    {
        try {
            $position_actual = $request->position_actual;
            $conceptoEstablecimiento = ConceptoEstablecimiento::where('id', $request->concepto_establecimiento_id)
                ->with(['funcionarios' => function ($q) use ($position_actual) {
                    $q->where('concepto_establecimiento_user.posicion', $position_actual);
                }])
                ->firstOrFail();
            $this->authorize('update', $conceptoEstablecimiento->concepto);
            $funcionario = $conceptoEstablecimiento->funcionarios->first();
            if ($funcionario) {
                $conceptoEstablecimiento->funcionarios()->detach($funcionario->id);
                $conceptoEstablecimiento = $conceptoEstablecimiento->fresh();

                $funcionarios = $conceptoEstablecimiento->funcionarios()->get();
                if (count($funcionarios) > 0) {
                    foreach ($funcionarios as $key => $funcionario) {
                        $funcionario->pivot->posicion = $key + 1;
                        $funcionario->pivot->save();
                    }
                }


                return response()->json([
                    'status'    => 'success',
                    'title'     => 'Usuario eliminado con éxito.',
                    'message'   => null,
                ]);
            } else {
                return response()->json(['error' => 'No se encontró ningún funcionario con la posición actual especificada.'], 400);
            }
        } catch (\Exception $error) {
            return response()->json(['error' => 'Ha ocurrido un error al eliminar el usuario.'], 500);
        }
    }
}
