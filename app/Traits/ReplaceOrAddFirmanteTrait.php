<?php

namespace App\Traits;

use App\Models\Concepto;
use App\Models\ConceptoEstablecimiento;
use App\Models\Solicitud;
use Carbon\Carbon;
use App\Traits\StatusSolicitudTrait;
use Illuminate\Support\Facades\Log;

trait ReplaceOrAddFirmanteTrait
{
    use StatusSolicitudTrait;

    public function getFirmanteReemplazar($solicitud, $role_id, $user_id)
    {
        try {
            if ($solicitud->tipo_comision_id === 5) {
                $concepto_establecimiento = ConceptoEstablecimiento::where('concepto_id', 2)
                    ->where('establecimiento_id', $solicitud->establecimiento_id)
                    ->first();

                if (!$concepto_establecimiento) {
                    return null;
                }

                $funcionario = $concepto_establecimiento->funcionarios->where('pivot.role_id', $role_id)->first();
                return $funcionario ? $funcionario : null;
            }
            return null;
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    public function addFirmantesGrupo($solicitud)
    {
        $firmantes_solicitud = [];
        $firmantes = $solicitud->grupo->firmantes()->where('status', true)->orderBy('posicion_firma', 'ASC')->get();
        if ($firmantes) {
            foreach ($firmantes as $firmante) {
                $status = true;
                if ($firmante->role_id === 6 || $firmante->role_id === 7) {
                    $status = true;
                    if (!$solicitud->derecho_pago) {
                        $status = false;
                    }
                }
                $funcionario_remplazo  = $this->getFirmanteReemplazar($solicitud, $firmante->role_id, $firmante->user_id);
                $firmantes_solicitud[] = [
                    'posicion_firma'    => $firmante->posicion_firma,
                    'status'            => $firmante->status,
                    'solicitud_id'      => $solicitud->id,
                    'grupo_id'          => $firmante->grupo_id,
                    'user_id'           => $funcionario_remplazo ? $funcionario_remplazo->id : $firmante->user_id,
                    'role_id'           => $firmante->role_id,
                    'status'            => $status,
                    'permissions_id'    => $this->getPermissions($firmante->role_id, $solicitud)
                ];
            }
        }
        $firmantes_cap_cen = [];
        if ($solicitud->tipo_comision_id === 5) {
            $firmantes_cap_cen = $this->getFirmantesEspecialesCapCen($solicitud, $firmantes_solicitud);
        }

        if (is_array($firmantes_cap_cen)  && count($firmantes_cap_cen) > 0) {
            $firmantes_complete = array_merge($firmantes_solicitud, $firmantes_cap_cen);
        } else {
            $firmantes_complete = $firmantes_solicitud;
        }

        $fi = $this->orderFirmantes($firmantes_complete);

        return $fi;
    }

    public function updateSolicitudFirmantes($solicitud)
    {
        $firmantes_solicitud    = $solicitud->firmantes()->where('role_id', '!=', 1)->orderBy('posicion_firma', 'ASC')->get()->toArray();

        if ($solicitud->tipo_comision_id === 5) {
            //tengo los nuevos firmantes a agregar al circuito
            $firmantes_cap_cen      = $this->getFirmantesEspecialesCapCen($solicitud, $firmantes_solicitud);

            //se deben agregar al circuito de firmas
            if (is_array($firmantes_cap_cen)  && count($firmantes_cap_cen) > 0) {
                $solicitud->addFirmantes($firmantes_cap_cen);
            }

            $solicitud = $solicitud->fresh();

            $this->orderFirmantesUpdate($solicitud);
        } else {
            $firmantes_solicitud    = $solicitud->firmantes()->where('role_id', '!=', 1)->get();
            $roles_id               = $solicitud->grupo->firmantes()->pluck('role_id')->toArray();
            foreach ($firmantes_solicitud as $firmante) {
                $firmante_grupo = $solicitud->grupo->firmantes()->where('role_id', $firmante->role_id)->first();

                $total_validaciones = $firmante->estados()->count();
                if (($firmante_grupo) && ($firmante_grupo->user_id !== $firmante->user_id)) {
                    $firmante->update(['user_id' => $firmante_grupo->user_id]);
                }
                $estados = $firmante->estados()->count();
                if(!$firmante_grupo && $estados <= 0){
                    $firmante->delete();
                }

                if (!in_array($firmante->role_id, $roles_id)) {
                    $firmante->update(['status' => false]);
                }
            }
            $this->refreshPosicion($solicitud);
        }

        $this->statusFirmantes($solicitud);
    }

    private function statusFirmantes($solicitud)
    {
        if (!$solicitud->derecho_pago) {
            $firmantes = $solicitud->firmantes()->whereIn('role_id', [6, 7])->where('status', true)->get();
            if (count($firmantes) > 0) {
                $firmantes->toQuery()->update([
                    'status' => false
                ]);
            }
        } else {
            $firmantes = $solicitud->firmantes()->whereIn('role_id', [6, 7])->where('status', false)->get();
            if (count($firmantes) > 0) {
                $firmantes->toQuery()->update([
                    'status' => true
                ]);
            }
        }
    }

    private function orderFirmantesUpdate($solicitud)
    {
        // Obtener los firmantes ordenados por posicion_firma
        $firmantes = $solicitud->firmantes()->where('role_id', '!=', 1)->orderBy('posicion_firma', 'ASC')->get();

        // Obtener el valor de id_permission_valorizacion_crear
        $id_permission_valorizacion_crear = $this->idPermission('solicitud.valorizacion.crear');

        // Validar que el array inicial no esté vacío
        if ($firmantes->isEmpty()) {
            return [];
        }

        // Acumular cambios
        $actualizaciones = [];

        foreach ($firmantes as $firmante) {
            $funcionario_remplazo = $this->getFirmanteReemplazar($solicitud, $firmante->role_id, $firmante->user_id);
            $firmante->update(['user_id' => $funcionario_remplazo ? $funcionario_remplazo->id : $firmante->user_id]);

            $firmante_role_10   = $firmante->role_id === 10 ? $firmante : null;
            $firmante_valoriza  = in_array($id_permission_valorizacion_crear, $firmante['permissions_id'] ?? []) ? $firmante : null;

            if ($firmante_role_10 && $firmante_valoriza) {
                $actualizaciones[] = [
                    'id'                => $firmante_role_10->id,
                    'posicion_firma'    => $firmante_valoriza->posicion_firma - 1,
                ];
            }

            if ($firmante->posicion_firma === 0 && $firmante_role_10) {
                $actualizaciones[] = [
                    'id'                => $firmante->id,
                    'posicion_firma'    => $firmante_role_10->posicion_firma + 1,
                ];
            }

            $firmante_revi_finanzas  = $firmante->role_id === 6 ? $firmante : null;
            $firmante_super_finanzas = $firmante->role_id === 7 ? $firmante : null;

            if ($firmante_revi_finanzas && $firmante_super_finanzas) {
                $actualizaciones[] = [
                    'id'                => $firmante_revi_finanzas->id,
                    'posicion_firma'    => $firmante_super_finanzas->posicion_firma - 1,
                ];
            }
        }

        // Aplicar todas las actualizaciones
        foreach ($actualizaciones as $update) {
            $solicitud->firmantes()->where('id', $update['id'])->update([
                'posicion_firma' => $update['posicion_firma']
            ]);
        }

        $solicitud = $solicitud->fresh();

        $this->refreshPosicion($solicitud);
    }

    public function refreshPosicion($solicitud)
    {
        $firmantes = $solicitud->firmantes()->orderBy('posicion_firma', 'ASC')->get();

        foreach ($firmantes as $key => $firmante) {
            $firmante->update(['posicion_firma' => $key]);
        }
    }

    private function getFirmantesEspecialesCapCen($solicitud, $firmantes_solicitud)
    {
        try {
            $conceptoEstablecimiento = ConceptoEstablecimiento::where('establecimiento_id', $solicitud->establecimiento_id)
                ->whereHas('concepto', function ($q) {
                    $q->where('nombre', 'CAPACITACIÓN FINANCIAMIENTO CENTRALIZADO');
                })->first();

            if ($conceptoEstablecimiento) {
                $roles_id_firmantes = collect($firmantes_solicitud)->pluck('role_id')->unique()->values()->all();
                $firmantes_adicionales          = [];
                $firmantes_adicionales_concepto = $conceptoEstablecimiento->funcionarios()
                    ->whereNotIn('role_id', $roles_id_firmantes)
                    ->get();

                if (count($firmantes_adicionales_concepto) > 0) {
                    foreach ($firmantes_adicionales_concepto as $firmante) {
                        $firmantes_adicionales[] =
                            [
                                'posicion_firma'    => 0,
                                'solicitud_id'      => $solicitud->id,
                                'grupo_id'          => $solicitud->grupo_id,
                                'user_id'           => $firmante->id,
                                'role_id'           => $firmante->pivot->role_id,
                                'status'            => true,
                                'permissions_id'    => $this->getPermissions($firmante->pivot->role_id, $solicitud)
                            ];
                    }
                    return $firmantes_adicionales;
                }
            }
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    private function orderFirmantes($firmantes_complete)
    {
        // Convertir el array en una colección
        $firmantes = collect($firmantes_complete);

        // Obtener el valor de id_permission_valorizacion_crear
        $id_permission_valorizacion_crear = $this->idPermission('solicitud.valorizacion.crear');

        // Validar que el array inicial no esté vacío
        if ($firmantes->isEmpty()) {
            return [];
        }

        // Buscar el elemento con role_id 10
        $firmante_role_10 = $firmantes->firstWhere('role_id', 10);

        // Buscar el índice del elemento que tiene el permissions_id que coincida con $id_permission_valorizacion_crear
        $index_permission = $firmantes->search(function ($firmante) use ($id_permission_valorizacion_crear) {
            return in_array($id_permission_valorizacion_crear, $firmante['permissions_id'] ?? []);
        });

        // Separar los elementos con posicion_firma igual a 0
        $firmantes_posicion_0 = $firmantes->filter(function ($firmante) {
            return $firmante['posicion_firma'] === 0;
        });

        // Eliminar los elementos con posicion_firma 0 y con role_id 10 de la colección original
        $firmantes = $firmantes->reject(function ($firmante) use ($firmante_role_10, $firmantes_posicion_0) {
            return $firmante === $firmante_role_10 || $firmantes_posicion_0->contains($firmante);
        })->values();

        // Convertir a array para trabajar con índices
        $firmantes_array = $firmantes->toArray();

        // Insertar el elemento con role_id 10 una posición antes del índice encontrado
        if ($firmante_role_10 && $index_permission !== false) {
            array_splice($firmantes_array, $index_permission, 0, [$firmante_role_10]);
        }

        // Buscar el nuevo índice del elemento con role_id 10
        $index_role_10 = collect($firmantes_array)->search(function ($firmante) use ($firmante_role_10) {
            return $firmante === $firmante_role_10;
        });

        // Insertar los elementos con posicion_firma 0 después de role_id 10
        if ($index_role_10 !== false && $firmantes_posicion_0->isNotEmpty()) {
            $firmantes_array = array_merge(
                array_slice($firmantes_array, 0, $index_role_10 + 1),
                $firmantes_posicion_0->toArray(),
                array_slice($firmantes_array, $index_role_10 + 1)
            );
        }

        // Verificar si existen los elementos con role_id 6 y role_id 7
        $firmante_role_6    = collect($firmantes_array)->firstWhere('role_id', 6);
        $index_role_7       = collect($firmantes_array)->search(fn($firmante) => $firmante['role_id'] === 7);

        // Insertar el elemento con role_id 6 antes del elemento con role_id 7
        if ($firmante_role_6 && $index_role_7 !== false) {
            // Remover el elemento con role_id 6 si ya existe
            $firmantes_array = collect($firmantes_array)
                ->reject(fn($firmante) => $firmante === $firmante_role_6)
                ->values()
                ->toArray();

            // Volver a calcular el índice de role_id 7 después de la eliminación
            $index_role_7 = collect($firmantes_array)->search(fn($firmante) => $firmante['role_id'] === 7);

            // Insertar el elemento con role_id 6 antes del índice de role_id 7
            array_splice($firmantes_array, $index_role_7, 0, [$firmante_role_6]);
        }

        // Reconvertir a colección
        $firmantes = collect($firmantes_array)->unique('role_id')->values();

        // Actualizar los valores de posicion_firma basados en la posición en la colección
        $firmantes = $firmantes->map(function ($firmante, $index) {
            $firmante['posicion_firma'] = $index + 1; // Comenzar desde 1
            return $firmante;
        });

        return $firmantes->toArray();
    }
}
