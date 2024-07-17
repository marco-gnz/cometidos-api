<?php

namespace App\Http\Controllers\Admin\Convenios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Convenio\StoreConvenioRequest;
use App\Http\Requests\Convenio\UpdateConvenioRequest;
use App\Http\Resources\Convenio\ConvenioEditResource;
use App\Http\Resources\Convenio\ConvenioResource;
use App\Http\Resources\Convenio\ListConvenioResource;
use App\Models\Convenio;
use App\Models\User;
use Illuminate\Http\Request;

class ConvenioController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getConvenios(Request $request)
    {
        try {
            $this->authorize('viewAny', Convenio::class);
            $convenios = Convenio::input($request->input)
                ->periodo($request->periodo)
                ->establecimiento($request->establecimientos_id)
                ->ley($request->leys_id)
                ->ilustre($request->ilustres_id)
                ->status($request->status)
                ->orderBy('codigo', 'ASC')
                ->paginate(50);

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'pagination' => [
                        'total'         => $convenios->total(),
                        'total_desc'    => $convenios->total() > 1 ? "{$convenios->total()} resultados" : "{$convenios->total()} resultado",
                        'current_page'  => $convenios->currentPage(),
                        'per_page'      => $convenios->perPage(),
                        'last_page'     => $convenios->lastPage(),
                        'from'          => $convenios->firstItem(),
                        'to'            => $convenios->lastPage()
                    ],
                    'data'          => ListConvenioResource::collection($convenios)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function getConvenio($uuid)
    {
        try {

            $convenio = Convenio::where('uuid', $uuid)->firstOrFail();
            $this->authorize('view', $convenio);
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => ConvenioResource::make($convenio)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function getConvenioEdit($uuid)
    {
        try {
            $convenio = Convenio::where('uuid', $uuid)->firstOrFail();
            $this->authorize('update', $convenio);

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => ConvenioEditResource::make($convenio)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function getUsers(Request $request)
    {
        try {
           $users = User::general($request->input)
           ->doesntHave('roles')
           ->get();

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'user'          => $users
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function storeConvenio(StoreConvenioRequest $request)
    {
        try {
            $this->authorize('create', Convenio::class);
            $form = [
                'fecha_inicio',
                'fecha_termino',
                'fecha_resolucion',
                'n_resolucion',
                'n_viatico_mensual',
                'anio',
                'observacion',
                'estamento_id',
                'ley_id',
                'establecimiento_id',
                'ilustre_id',
                'user_id',
                'tipo_contrato',
                'email'
            ];
            $convenio = Convenio::create($request->only($form));

            if($convenio){
                $convenio = $convenio->fresh();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Convenio ingresado con éxito.",
                        'message'       => null,
                        'data'          => ListConvenioResource::make($convenio)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function updateConvenio($uuid, UpdateConvenioRequest $request)
    {
        try {
            $form = [
                'fecha_inicio',
                'fecha_termino',
                'fecha_resolucion',
                'n_resolucion',
                'n_viatico_mensual',
                'anio',
                'observacion',
                'estamento_id',
                'ley_id',
                'establecimiento_id',
                'ilustre_id',
                'tipo_contrato',
                'email'
            ];
            $convenio = Convenio::where('uuid', $uuid)->firstOrFail();
            $this->authorize('update', $convenio);

            $update = $convenio->update($request->only($form));

            if($update){
                $convenio = $convenio->fresh();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Convenio modificado con éxito.",
                        'message'       => null,
                        'data'          => ListConvenioResource::make($convenio)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function updateConvenioStatus($uuid)
    {
        try {
            $convenio = Convenio::where('uuid', $uuid)->firstOrFail();
            $this->authorize('delete', $convenio);
            $update = $convenio->update(['active' => !$convenio->active]);

            if ($update) {
                $convenio = $convenio->fresh();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Convenio modificado con éxito.",
                        'message'       => null,
                        'data'          => ListConvenioResource::make($convenio)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function deleteConvenio($uuid)
    {
        try {
            $convenio = Convenio::where('uuid', $uuid)->firstOrFail();
            $this->authorize('delete', $convenio);
            $delete = $convenio->delete();
            if($delete){
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Convenio eliminado con éxito.",
                        'message'       => null,
                        'data'          => null
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
}
