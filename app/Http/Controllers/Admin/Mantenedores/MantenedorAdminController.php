<?php

namespace App\Http\Controllers\Admin\Mantenedores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mantenedores\Lugar\StoreLugarRequest;
use App\Http\Resources\Mantenedores\LugaresResource;
use App\Models\Lugar;
use Illuminate\Http\Request;

class MantenedorAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getLugares(Request $request)
    {
        try {
            $this->authorize('viewAny', Lugar::class);
            $lugares = Lugar::input($request->input)->orderBy('nombre', 'ASC')->paginate(50);

            return response()->json(
                array(
                    'status'        => 'success',
                    'pagination' => [
                        'total'         => $lugares->total(),
                        'total_desc'    => $lugares->total() > 1 ? "{$lugares->total()} resultados" : "{$lugares->total()} resultado",
                        'current_page'  => $lugares->currentPage(),
                        'per_page'      => $lugares->perPage(),
                        'last_page'     => $lugares->lastPage(),
                        'from'          => $lugares->firstItem(),
                        'to'            => $lugares->lastPage()
                    ],
                    'data'          => LugaresResource::collection($lugares)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function changeStatusLugar($id)
    {
        try {
            $lugar = Lugar::find($id);

            if ($lugar) {
                $this->authorize('update', $lugar);
                $update = $lugar->update(['active' => !$lugar->active]);

                if ($update) {
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => "Lugar modificado con éxito.",
                            'message'       => null,
                            'data'          => LugaresResource::make($lugar)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function storeLugar(StoreLugarRequest $request)
    {
        try {
            $this->authorize('create', Lugar::class);
            $form   = ['nombre'];
            $lugar  = Lugar::create($request->only($form));
            if ($lugar) {
                $lugar = $lugar->fresh();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Lugar ingresado con éxito.",
                        'message'       => null,
                        'data'          => LugaresResource::make($lugar)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
}
