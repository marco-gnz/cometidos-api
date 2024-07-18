<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreContratoRequest;
use App\Http\Requests\User\StoreCuentaBancariaRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateContratoRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\Admin\ListUsersResource;
use App\Http\Resources\Admin\UserResource;
use App\Http\Resources\Admin\UserUpdateResource;
use App\Models\Contrato;
use App\Models\CuentaBancaria;
use App\Models\Grupo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            $this->authorize('viewAny', User::class);
            $auth               = Auth::user();
            $establecimientos   = $auth->establecimientos->pluck('id')->toArray();
            $leyes              = $auth->leyes->pluck('id')->toArray();
            $departamentos      = $auth->departamentos->pluck('id')->toArray();

            $users = User::where(function ($q) use ($establecimientos, $leyes, $departamentos) {
                if ($establecimientos) {
                    $q->whereHas('contratos', function ($query) use ($establecimientos) {
                        $query->whereIn('establecimiento_id', $establecimientos);
                    });
                }

                if ($leyes) {
                    $q->whereHas('contratos', function ($query) use ($leyes) {
                        $query->whereIn('ley_id', $leyes);
                    });
                }

                if ($departamentos) {
                    $q->whereHas('contratos', function ($query) use ($departamentos) {
                        $query->whereIn('departamento_id', $departamentos);
                    });
                }
            })
                ->orWhereDoesntHave('contratos')
                ->general($request->input)
                ->establecimiento($request->establecimientos_id)
                ->depto($request->deptos_id)
                ->subdepto($request->subdeptos_id)
                ->grado($request->grados_id)
                ->ley($request->leys_id)
                ->orderBy('apellidos', 'ASC')
                ->paginate(50);

            return response()->json([
                'status'            => 'success',
                'title'             => null,
                'message'           => null,
                'pagination'        => [
                    'total'         => $users->total(),
                    'total_desc'    => $users->total() > 1 ? "{$users->total()} resultados" : "{$users->total()} resultado",
                    'current_page'  => $users->currentPage(),
                    'per_page'      => $users->perPage(),
                    'last_page'     => $users->lastPage(),
                    'from'          => $users->firstItem(),
                    'to'            => $users->lastItem()
                ],
                'data' => ListUsersResource::collection($users)
            ]);
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function getUser($uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->firstOrFail();
            $this->authorize('view', $user);
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
            $this->authorize('update', $user);

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
            $this->authorize('create', User::class);
            DB::beginTransaction();
            $data_user = [
                'rut'       => $request->rut,
                'dv'        => $request->dv,
                'nombres'   => $request->nombres,
                'apellidos' => $request->apellidos,
                'email'     => $request->email
            ];

            $user = User::create($data_user);

            if ($user) {
                $data = [
                    'ley_id'                => $request->ley_id,
                    'estamento_id'          => $request->estamento_id,
                    'grado_id'              => $request->grado_id,
                    'cargo_id'              => $request->cargo_id,
                    'departamento_id'       => $request->departamento_id,
                    'sub_departamento_id'   => $request->sub_departamento_id,
                    'establecimiento_id'    => $request->establecimiento_id,
                    'hora_id'               => $request->hora_id,
                    'calidad_id'            => $request->calidad_id,
                ];
                $contrato = Contrato::create($data);
                if ($contrato) {
                    $user->contratos()->save($contrato);
                    $user = $user->fresh();
                    DB::commit();
                    return response()->json(
                        array(
                            'status'        => 'success',
                            'title'         => "Funcionario ingresado con éxito.",
                            'message'       => null,
                            'data'          => ListUsersResource::make($user)
                        )
                    );
                }
            }
        } catch (\Exception $error) {
            DB::rollback();
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    private function existeContrato($user, $data)
    {
        return $user->contratos()->where($data)->exists();
    }

    public function userUpdate($uuid, UpdateUserRequest $request)
    {
        try {
            $user = User::where('uuid', $uuid)->firstOrFail();
            $this->authorize('update', $user);
            $form = [
                'rut',
                'dv',
                'nombres',
                'apellidos',
                'email',
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
            $this->authorize('update', $user);
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
            $this->authorize('update', $user);
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
            $this->authorize('update', $cuenta->funcionario);
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
            $this->authorize('update', $user);

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

    public function deleteContrato($uuid)
    {
        try {
            $contrato = Contrato::where('uuid', $uuid)->firstOrFail();
            $this->authorize('update', $contrato->funcionario);
            $delete = $contrato->delete();
            if ($delete) {
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Contrato eliminado con éxito.",
                        'message'       => null,
                        'data'          => null
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function storeContrato(StoreContratoRequest $request)
    {
        try {
            $user = User::where('uuid', $request->user_uuid)->firstOrFail();
            $this->authorize('update', $user);

            $data = [
                'ley_id'                => $request->ley_id,
                'estamento_id'          => $request->estamento_id,
                'grado_id'              => $request->grado_id,
                'cargo_id'              => $request->cargo_id,
                'departamento_id'       => $request->departamento_id,
                'sub_departamento_id'   => $request->sub_departamento_id,
                'establecimiento_id'    => $request->establecimiento_id,
                'hora_id'               => $request->hora_id,
                'calidad_id'            => $request->calidad_id,
            ];

            $existe_contrato = $this->existeContrato($user, $data);

            if ($existe_contrato) {
                return response()->json([
                    'errors' => ['establecimiento_id' => ['Contrato ya existe.']]
                ], 422);
            }

            $contrato = Contrato::create($data);

            if ($contrato) {
                $user->contratos()->save($contrato);
                $user = $user->fresh();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Contrato ingresado con éxito.",
                        'message'       => null,
                        'data'          => UserResource::make($user)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function updateGrupoContrato(Request $request)
    {
        try {
            $contrato = Contrato::where('uuid', $request->contrato_uuid)->firstOrFail();
            $grupo    = Grupo::where('id', $request->grupo_id)->first();

            $update = $contrato->update([
                'grupo_id'  => $grupo ? $grupo->id : NULL
            ]);

            if ($update) {
                $user = $contrato->funcionario->fresh();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Contrato modificado con éxito.",
                        'message'       => null,
                        'data'          => UserResource::make($user)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function updateContrato($uuid, UpdateContratoRequest $request)
    {
        try {
            $contrato = Contrato::where('uuid', $uuid)->firstOrFail();
            $this->authorize('update', $contrato->funcionario);
            $data = [
                'ley_id'                => $request->ley_id,
                'estamento_id'          => $request->estamento_id,
                'grado_id'              => $request->grado_id,
                'cargo_id'              => $request->cargo_id,
                'departamento_id'       => $request->departamento_id,
                'sub_departamento_id'   => $request->sub_departamento_id,
                'establecimiento_id'    => $request->establecimiento_id,
                'hora_id'               => $request->hora_id,
                'calidad_id'            => $request->calidad_id,
            ];

            if (
                $contrato->establecimiento_id !== $request->establecimiento_id ||
                $contrato->departamento_id !== $request->departamento_id ||
                $contrato->sub_departamento_id !== $request->sub_departamento_id
            ) {
                $data['grupo_id'] = NULL;
            }

            $update = $contrato->update($data);

            if ($update) {
                $user = $contrato->funcionario->fresh();
                return response()->json(
                    array(
                        'status'        => 'success',
                        'title'         => "Contrato modificado con éxito.",
                        'message'       => null,
                        'data'          => UserResource::make($user)
                    )
                );
            }
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
}
