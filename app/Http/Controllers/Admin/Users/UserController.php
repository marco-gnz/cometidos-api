<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreCuentaBancariaRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\Admin\ListUsersResource;
use App\Http\Resources\Admin\UserResource;
use App\Http\Resources\Admin\UserUpdateResource;
use App\Models\CuentaBancaria;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function listUsers(Request $request)
    {
        try {
            $users = User::general($request->input)
                ->establecimiento($request->establecimientos_id)
                ->depto($request->deptos_id)
                ->grado($request->grados_id)
                ->ley($request->leys_id)
                ->orderBy('apellidos', 'ASC')
                ->paginate(50);

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'pagination' => [
                        'total'         => $users->total(),
                        'total_desc'    => $users->total() > 1 ? "{$users->total()} resultados" : "{$users->total()} resultado",
                        'current_page'  => $users->currentPage(),
                        'per_page'      => $users->perPage(),
                        'last_page'     => $users->lastPage(),
                        'from'          => $users->firstItem(),
                        'to'            => $users->lastPage()
                    ],
                    'data'          => ListUsersResource::collection($users)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function getUser($uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->firstOrFail();

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => UserResource::make($user)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function getUserUpdate($uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->firstOrFail();

            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => null,
                    'message'       => null,
                    'data'          => UserUpdateResource::make($user)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function storeUser(StoreUserRequest $request)
    {
        try {
            $form = [
                'rut',
                'dv',
                'nombres',
                'apellidos',
                'email',
                'establecimiento_id',
                'departamento_id',
                'sub_departamento_id',
                'estamento_id',
                'cargo_id',
                'calidad_id',
                'hora_id',
                'ley_id',
                'grado_id'
            ];

            $user = User::create($request->only($form));

            if ($user) {
                $user = $user->fresh();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Funcionario ingresado con éxito.",
                        'message'       => null,
                        'data'          => ListUsersResource::make($user)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function userUpdate($uuid, UpdateUserRequest $request)
    {
        try {
            $user = User::where('uuid', $uuid)->firstOrFail();

            $form = [
                'rut',
                'dv',
                'nombres',
                'apellidos',
                'email',
                'establecimiento_id',
                'departamento_id',
                'sub_departamento_id',
                'estamento_id',
                'cargo_id',
                'calidad_id',
                'hora_id',
                'ley_id',
                'grado_id'
            ];
            $update = $user->update($request->only($form));

            if ($update) {
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Funcionario modificado con éxito.",
                        'message'       => null,
                        'data'          => ListUsersResource::make($user)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function updateStatusUser($uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->firstOrFail();

            $update = $user->update([
                'estado'    => !$user->estado
            ]);
            if ($update) {
                $user = $user->fresh();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Estado modificado con éxito.",
                        'message'       => null,
                        'data'          => ListUsersResource::make($user)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function updatePermisoPrincipalUser(Request $request)
    {
        try {
            $user = User::where('uuid', $request->user_uuid)->firstOrFail();

            $permisos = [
                'solicitud' => 'is_solicitud',
                'informe'   => 'is_informe',
                'rendicion' => 'is_rendicion',
                'suborante' => 'is_subrogante'
            ];

            // Verificar si el permiso es válido
            if (!isset($permisos[$request->permiso])) {
                return response()->json([
                    'errors' => "No existe permiso."
                ], 422);
            }

            $campo  = $permisos[$request->permiso];
            $update = $user->update([$campo => !$user->$campo]);

            if ($update) {

                $user = $user->fresh();
                return response()->json([
                    'status'    => 'success',
                    'title'     => "Permiso modificado con éxito.",
                    'message'   => null,
                    'data'      => UserResource::make($user)
                ]);
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }


    public function updateStatusCuentaBancaria($uuid)
    {
        try {
            $cuenta = CuentaBancaria::where('uuid', $uuid)->firstOrFail();

            $cuentas_total = $cuenta->funcionario->cuentas()->where('status', true)->count();

            if (!$cuenta->status && $cuentas_total > 0) {
                return response()->json([
                    'errors' => "Ya existe una cuenta habilitada."
                ], 422);
            } else {
                $update = $cuenta->update([
                    'status'    => !$cuenta->status
                ]);
                if ($update) {
                    $user = $cuenta->funcionario->fresh();
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => "Cuenta bancaria modificada con éxito.",
                            'message'       => null,
                            'data'          => UserResource::make($user)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function storeCuentaBancaria(StoreCuentaBancariaRequest $request)
    {
        try {
            $user = User::where('uuid', $request->user_uuid)->firstOrFail();

            $cuentas_total = $user->cuentas()->where('status', true)->count();
            $status = true;

            if ($request->tipo_cuenta === CuentaBancaria::TYPE_ACCOUNT_6) {
                $cuentas_cash = $user->cuentas()
                    ->where('tipo_cuenta', CuentaBancaria::TYPE_ACCOUNT_6)
                    ->count();

                if ($cuentas_cash > 0) {
                    return response()->json([
                        'errors' => ['tipo_cuenta' => [CuentaBancaria::TYPE_ACCOUNT_NOM[$request->tipo_cuenta] . " ya existe."]]
                    ], 422);
                }
            }
            if ($cuentas_total > 0) {
                $status = false;
            }
            $cuentas[] = [
                'tipo_cuenta'   => $request->tipo_cuenta,
                'n_cuenta'      => $request->n_cuenta,
                'banco_id'      => $request->banco_id,
                'status'        => $status
            ];
            $user->addCuentas($cuentas);
            $user = $user->fresh();
            return response()->json(
                array(
                    'status'        => 'success',
                    'title'         => "Cuenta bancaria ingresada con éxito.",
                    'message'       => null,
                    'data'          => UserResource::make($user)
                )
            );
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
}