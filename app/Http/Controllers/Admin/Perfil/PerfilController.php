<?php

namespace App\Http\Controllers\Admin\Perfil;

use App\Http\Controllers\Controller;
use App\Http\Requests\Perfil\StorePerfilRequest;
use App\Http\Requests\Perfil\UpdatePerfilRequest;
use App\Http\Resources\Admin\EditPerfilResource;
use App\Http\Resources\Admin\ListPerfilResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class PerfilController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getPerfiles(Request $request)
    {
        try {
            $this->authorize('viewAnyPerfil', User::class);
            $names_roles = ['VISOR','ADMINISTRADOR', 'SUPER ADMINISTRADOR'];
            $roles       = Role::whereIn('name', $names_roles)->pluck('name')->toArray();
            $perfiles    = User::role($roles)
                ->general($request->input)
                ->establecimientos($request->establecimientos_id)
                ->departamentos($request->deptos_id)
                ->leyes($request->leys_id)
                ->perfiles($request->perfiles_id)
                ->orderBy('apellidos', 'ASC')
                ->paginate(30);

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'pagination' => [
                        'total'         => $perfiles->total(),
                        'total_desc'    => $perfiles->total() > 1 ? "{$perfiles->total()} resultados" : "{$perfiles->total()} resultado",
                        'current_page'  => $perfiles->currentPage(),
                        'per_page'      => $perfiles->perPage(),
                        'last_page'     => $perfiles->lastPage(),
                        'from'          => $perfiles->firstItem(),
                        'to'            => $perfiles->lastPage()
                    ],
                    'perfiles'  => ListPerfilResource::collection($perfiles),
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function deletePerfil($uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->firstOrFail();
            $this->authorize('deletePerfil', $user);
            $user->syncRoles([]);
            $user->establecimientos()->detach();
            $user->leyes()->detach();
            $user->departamentos()->detach();
            $user->syncPermissions([]);

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => "Perfil eliminado con éxito.",
                    'message'       => null,
                    'data'          => $user
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function getPerfilEdit($uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->firstOrFail();
            $this->authorize('updatePerfil', $user);
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => "Perfil eliminado con éxito.",
                    'message'       => null,
                    'perfil'        => EditPerfilResource::make($user)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function storePerfil(StorePerfilRequest $request)
    {
        try {
            $this->authorize('createPerfil', User::class);
            DB::beginTransaction();
            $user = User::find($request->user_id);

            if($user){
                $user->syncRoles($request->perfiles_id ?? []);
                $user->establecimientos()->sync($request->establecimientos_id ?? []);
                $user->leyes()->sync($request->leys_id ?? []);
                $user->transportes()->sync($request->medios_transporte_id ?? []);
                $user->departamentos()->sync($request->deptos_id ?? []);
                $user->syncPermissions($request->permissions_id ?? []);

                $user = $user->fresh();
                DB::commit();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Perfil ingresado con éxito.",
                        'message'       => null,
                        'perfil'        => ListPerfilResource::make($user)
                    )
                );
            }

        } catch (\Exception $error) {
            DB::rollback();
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function updatePerfil($uuid, UpdatePerfilRequest $request)
    {
        try {
            $user = User::where('uuid', $uuid)->firstOrFail();
            $this->authorize('updatePerfil', $user);
            $user->syncRoles($request->perfiles_id ?? []);
            $user->establecimientos()->sync($request->establecimientos_id ?? []);
            $user->leyes()->sync($request->leys_id ?? []);
            $user->departamentos()->sync($request->deptos_id ?? []);
            $user->transportes()->sync($request->medios_transporte_id ?? []);
            $user->syncPermissions($request->permissions_id ?? []);

            $user = $user->fresh();
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => "Perfil modificado con éxito.",
                    'message'       => null,
                    'perfil'        => ListPerfilResource::make($user)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
}
