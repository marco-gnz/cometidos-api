<?php

namespace App\Traits;

use App\Models\EstadoInformeCometido;
use App\Models\EstadoProcesoRendicionGasto;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

trait FirmaDisponibleTrait
{
    private function idPermission($name_permission)
    {
        $permission = Permission::where('name', $name_permission)->first();
        if (!$permission) {
            return null;
        }
        return $permission->id;
    }

    private function obtenerPrimerFirmanteIsPermission($solicitud, $id_permission)
    {
        $is_subrogante          = false;
        $is_firma               = false;
        $auth                   = auth()->user();
        $id_user_ejecuted_firma = null;

        $query = $solicitud->firmantes()
            ->where('status', true)
            ->whereJsonContains('permissions_id', $id_permission)
            ->orderBy('posicion_firma', 'ASC');

        $query->when($auth, function ($query) use ($auth) {
            $query->where('user_id', $auth->id);
        });

        $primerFirmante = $query->first();

        if ($primerFirmante) {
            $id_user_ejecuted_firma = $primerFirmante->user_id;
        }

        if (!$primerFirmante) {
            $fecha_by_solicitud = Carbon::parse($solicitud->fecha_by_user)->format('Y-m-d');
            $primerFirmante = $solicitud->firmantes()
                ->whereJsonContains('permissions_id', $id_permission)
                ->where(function ($q) use ($auth, $fecha_by_solicitud) {
                    $q->whereHas('funcionario.reasignacionAusencias', function ($q) use ($auth, $fecha_by_solicitud) {
                        $q->where('user_subrogante_id', $auth->id);
                    });
                })
                ->orWhere(function ($q) use ($auth, $fecha_by_solicitud) {
                    $q->whereHas('funcionario.ausentismos', function ($q) use ($auth, $fecha_by_solicitud) {
                        $q->whereHas('subrogantes', function ($q) use ($auth, $fecha_by_solicitud) {
                            $q->where('users.id', $auth->id);
                        })->where(function ($q)  use ($fecha_by_solicitud) {
                            $q->where(function ($query) use ($fecha_by_solicitud) {
                                $query->where('fecha_inicio', '<=', $fecha_by_solicitud)
                                    ->where('fecha_termino', '>=', $fecha_by_solicitud);
                            })->orWhere(function ($query) use ($fecha_by_solicitud) {
                                $query->where('fecha_inicio', '<=', $fecha_by_solicitud)
                                    ->where('fecha_termino', '>=', $fecha_by_solicitud);
                            })->orWhere(function ($query) use ($fecha_by_solicitud) {
                                $query->where('fecha_inicio', '>=', $fecha_by_solicitud)
                                    ->where('fecha_termino', '<=', $fecha_by_solicitud);
                            });
                        });
                    });
                })
                ->where('solicitud_id', $solicitud->id)
                ->orderBy('posicion_firma', 'ASC')
                ->first();

            if ($primerFirmante) {
                $id_user_ejecuted_firma = auth()->user()->id;
                $is_subrogante = true;
            }
        }

        $data = (object) [
            'firma'                     => $primerFirmante,
            'is_subrogante'             => $is_subrogante,
            'is_firma'                  => $primerFirmante ? true : false,
            'id_user_ejecuted_firma'    => $id_user_ejecuted_firma
        ];

        return $data;
    }

    private function obtenerPrimerFirmanteHabilitado($solicitud, $id_permission)
    {
        $query = $solicitud->firmantes()
            ->whereJsonContains('permissions_id', $id_permission)
            ->where('status', true)
            ->orderBy('posicion_firma', 'ASC');

        if (!$solicitud->is_reasignada) {
            $query->where('posicion_firma', '>', $solicitud->posicion_firma_actual);
        } else {
            $query->where('posicion_firma', $solicitud->posicion_firma_actual);
        }

        return $query->first();
    }

    public function obtenerFirmaDisponible($solicitud, $name_permission)
    {
        $auth                   = Auth::user();
        $title                  = null;
        $message                = null;
        $type                   = null;
        $if_buttom              = false;
        $is_firma               = false;
        $id_firma               = null;
        $id_user_ejecuted_firma = null;
        $posicion_firma         = null;
        $is_subrogancia         = false;

        if ($solicitud->status === Solicitud::STATUS_PROCESADO && $solicitud->authorizedToReasignarEmergency()) {
            $name_user  = $auth->abreNombres();
            $title      = "{$name_user}, registras firma disponible de emergencia.";
            $type       = 'warning';
            $is_firma   = true;
            $if_buttom  = true;
        } else {
            $id_permission                      = $this->idPermission($name_permission);
            $first_firma_habilitada_solicitud   = $this->obtenerPrimerFirmanteHabilitado($solicitud, $id_permission);

            if ($first_firma_habilitada_solicitud) {
                $first_firma_auth = $solicitud->firmantes()
                    ->where('user_id', $auth->id)
                    ->where('id', $first_firma_habilitada_solicitud->id)
                    ->first();

                //es firma de usuario auth
                if ($first_firma_auth) {
                    $is_firma                           = true;
                    $if_buttom                          = true;
                    $id_firma                           = $first_firma_auth->id;
                    $id_user_ejecuted_firma             = $first_firma_auth->funcionario->id;
                    $posicion_firma                     = $first_firma_auth->posicion_firma;
                    $name_user                          = $first_firma_auth->funcionario->abreNombres();
                    $id_permission_valorizacion_crear   = $this->idPermission('solicitud.valorizacion.crear');
                    $get_last_calculo                   = $solicitud->getLastCalculo();
                    $total_informes_aprobados           = $solicitud->informes()->where('last_status', EstadoInformeCometido::STATUS_APROBADO)->count();

                    if (in_array($id_permission_valorizacion_crear, $first_firma_auth->permissions_id) && !$get_last_calculo) {
                        $is_firma   = false;
                        $type       = 'warning';
                        $title      = "{$name_user}, si registras firma disponible, pero existen tareas por ejecutar.";
                        $message    = "Se debe aplicar valorización a solicitud de cometido.";
                    } else {
                        $next_firma = $solicitud->firmantes()
                            ->where('status', true)
                            ->where('posicion_firma', '>', $first_firma_auth->posicion_firma)
                            ->orderBy('posicion_firma', 'ASC')
                            ->first();

                        if ($next_firma) {
                            $type       = 'success';
                            $title      = "{$name_user}, si registras firma disponible.";
                            $message    = "Al aprobar, la solicitud se derivará a la firma N° {$next_firma->posicion_firma}, ejecutada por {$next_firma->funcionario->abreNombres()} - {$next_firma->perfil->name}.";
                        } else {
                            $type           = 'warning';
                            $title          = "{$name_user}, registras como último firmante.";
                            $estado_finish  = Solicitud::STATUS_NOM[Solicitud::STATUS_PROCESADO];
                            $message        = "Al aprobar finalizará el ciclo de firma y la solicitud será {$estado_finish}";
                        }
                    }
                } else {
                    $fecha_by_solicitud = Carbon::parse($solicitud->fecha_by_user)->format('Y-m-d');
                    $first_firma_position = $solicitud->firmantes()
                        ->where(function ($q) use ($first_firma_habilitada_solicitud) {
                            $q->where('id', $first_firma_habilitada_solicitud->id)
                                ->where('is_executed', false);
                        })
                        ->where(function ($q) use ($auth, $fecha_by_solicitud) {
                            $q->whereHas('funcionario.reasignacionAusencias', function ($q) use ($auth, $fecha_by_solicitud) {
                                $q->where('user_subrogante_id', $auth->id);
                            });
                        })
                        ->orWhere(function ($q) use ($auth, $fecha_by_solicitud) {
                            $q->whereHas('funcionario.ausentismos', function ($q) use ($auth, $fecha_by_solicitud) {
                                $q->whereHas('subrogantes', function ($q) use ($auth, $fecha_by_solicitud) {
                                    $q->where('users.id', $auth->id);
                                })->where(function ($q)  use ($fecha_by_solicitud) {
                                    $q->where(function ($query) use ($fecha_by_solicitud) {
                                        $query->where('fecha_inicio', '<=', $fecha_by_solicitud)
                                            ->where('fecha_termino', '>=', $fecha_by_solicitud);
                                    })->orWhere(function ($query) use ($fecha_by_solicitud) {
                                        $query->where('fecha_inicio', '<=', $fecha_by_solicitud)
                                            ->where('fecha_termino', '>=', $fecha_by_solicitud);
                                    })->orWhere(function ($query) use ($fecha_by_solicitud) {
                                        $query->where('fecha_inicio', '>=', $fecha_by_solicitud)
                                            ->where('fecha_termino', '<=', $fecha_by_solicitud);
                                    });
                                });
                            });
                        })
                        ->where('solicitud_id', $solicitud->id)
                        ->first();

                    if ($first_firma_position) {
                        $name_user                          = $auth->abreNombres();
                        $if_buttom                          = true;
                        $get_last_calculo                   = $solicitud->getLastCalculo();
                        $id_permission_valorizacion_crear   = $this->idPermission('solicitud.valorizacion.crear');
                        if (in_array($id_permission_valorizacion_crear, $first_firma_position->permissions_id) && !$get_last_calculo) {
                            $is_firma   = false;
                            $type       = 'warning';
                            $title      = "{$name_user}, si registras firma disponible, pero existen tareas por ejecutar.";
                            $message    = "Se debe aplicar valorización a solicitud de cometido.";
                        } else {
                            $if_buttom              = false;
                            $is_firma               = true;
                            $id_firma               = $first_firma_position->id;
                            $id_user_ejecuted_firma = $auth->id;
                            $posicion_firma         = $first_firma_position->posicion_firma;
                            $is_subrogancia         = true;

                            $name_user  = $auth->abreNombres();
                            $next_firma = $solicitud->firmantes()
                                ->where('status', true)
                                ->where('posicion_firma', '>', $first_firma_position->posicion_firma)
                                ->orderBy('posicion_firma', 'ASC')
                                ->first();

                            if ($next_firma) {
                                $type       = 'warning';
                                $title      = "{$name_user}, registras firma disponible como subrogancia.";
                                $message    = "Al aprobar, la solicitud se derivará a la firma N° {$next_firma->posicion_firma}, ejecutada por {$next_firma->funcionario->abreNombres()} - {$next_firma->perfil->name}.";
                            } else {
                                $type           = 'warning';
                                $title          = "{$name_user}, registras como último firmante y subrogante.";
                                $estado_finish  = Solicitud::STATUS_NOM[Solicitud::STATUS_PROCESADO];
                                $message        = "Al aprobar finalizará el ciclo de firma y la solicitud será {$estado_finish}";
                            }
                        }
                    } else {
                        $title      = 'No es posible aplicar verificación.';
                        $message    = "No registras firmas disponibles o no es el turno de firma.";
                        $type       = 'error';
                    }
                }
            } else {
                $title      = 'No es posible aplicar verificación.';
                $message    = "Solicitud ya no registra firmas disponibles.";
                $type       = 'error';
            }
        }

        $data = (object) [
            'title'                     => $title,
            'message'                   => $message,
            'type'                      => $type,
            'is_firma'                  => $is_firma,
            'if_buttom'                 => $if_buttom,
            'id_firma'                  => $id_firma,
            'id_user_ejecuted_firma'    => $id_user_ejecuted_firma,
            'posicion_firma_solicitud'  => $solicitud->posicion_firma_actual,
            'posicion_firma'            => $posicion_firma,
            'is_subrogante'             => $is_subrogancia
        ];

        return $data;
    }

    public function firmaFuncionarioSolicitud($solicitud)
    {
        return $solicitud->firmantes()->where('role_id', 1)->first();
    }

    public function isFirmaDisponibleActionAnular($solicitud, $name_permission)
    {
        $id_permission = $this->idPermission($name_permission);
        if ($id_permission === null) {
            return (object) [
                'type'                      => 'warning',
                'is_firma'                  => false,
                'if_buttom'                 => false,
                'posicion_firma_solicitud'  => null,
                'posicion_firma'            => null,
                'firma'                     => null,
                'title'                     => 'Firma no disponible',
                'message'                   => 'Firma no disponible'
            ];
        }
    }
    public function isFirmaDisponibleAction($solicitud, $name_permission)
    {
        if ($name_permission === null) {
            $first_firma_habilitada_solicitud = $this->firmaFuncionarioSolicitud($solicitud);
            return (object) [
                'type'      => 'success',
                'is_firma'  => $first_firma_habilitada_solicitud ? true : false,
                'if_buttom' => $first_firma_habilitada_solicitud ? true : false,
                'firma'     => $first_firma_habilitada_solicitud,
                'posicion_firma_solicitud'  => $first_firma_habilitada_solicitud ? $first_firma_habilitada_solicitud->posicion_firma : null,
                'posicion_firma'    => null,
                'title'     => 'Firma disponible',
                'message'   => 'Firma disponible'
            ];
        }
        $id_permission = $this->idPermission($name_permission);
        if ($id_permission === null) {
            return (object) [
                'type'      => 'warning',
                'is_firma'  => false,
                'if_buttom' => false,
                'posicion_firma_solicitud'  => null,
                'posicion_firma'    => null,
                'firma'     => null,
                'title'     => 'Firma no disponible',
                'message'   => 'Firma no disponible'
            ];
        }

        $first_firma_habilitada_solicitud = $this->obtenerPrimerFirmanteHabilitado($solicitud, $id_permission);

        return (object) [
            'type'                      => 'success',
            'is_firma'                  => $first_firma_habilitada_solicitud ? true : false,
            'if_buttom'                 => $first_firma_habilitada_solicitud ? true : false,
            'firma'                     => $first_firma_habilitada_solicitud,
            'posicion_firma'            => null,
            'posicion_firma_solicitud'  => $first_firma_habilitada_solicitud ? $first_firma_habilitada_solicitud->posicion_firma : null,
            'title'                     => 'Firma disponible',
            'message'                   => 'Firma disponible'
        ];
    }

    public function isFirmaDisponibleActionPolicy($solicitud, $name_permission)
    {
        $id_permission = $this->idPermission($name_permission);
        if ($id_permission === null) {
            return (object) [
                'type'      => 'warning',
                'is_firma'  => false,
                'firma'     => null,
                'is_subrogante' => false,
                'title'     => 'Firma no disponible',
                'message'   => 'Firma no disponible'
            ];

            return (object) [
                'type'      => 'warning',
                'is_firma'  => false,
                'firma'     => null,
                'is_subrogante' => false,
                'if_buttom'     =>  false,
                'id_firma'      => null,
                'id_user_ejecuted_firma'   => null,
                'posicion_firma_solicitud'  => null,
                'posicion_firma'            => null,
                'title'     => 'Firma disponible',
                'message'   => 'Firma disponible'
            ];
        }

        $first_firma_habilitada_solicitud = $this->obtenerPrimerFirmanteIsPermission($solicitud, $id_permission);

        return (object) [
            'type'                      => 'success',
            'is_firma'                  => $first_firma_habilitada_solicitud->is_firma,
            'firma'                     => $first_firma_habilitada_solicitud->firma,
            'is_subrogante'             => $first_firma_habilitada_solicitud->is_subrogante,
            'if_buttom'                 => $first_firma_habilitada_solicitud->is_firma ? true : false,
            'id_firma'                  => $first_firma_habilitada_solicitud->is_firma ? $first_firma_habilitada_solicitud->firma->id : null,
            'id_user_ejecuted_firma'    => $first_firma_habilitada_solicitud->is_firma ? $first_firma_habilitada_solicitud->id_user_ejecuted_firma : null,
            'posicion_firma_solicitud'  => $solicitud->posicion_firma_actual,
            'posicion_firma'            => $first_firma_habilitada_solicitud->is_firma ? $first_firma_habilitada_solicitud->firma->posicion_firma : null,
            'title'                     => 'Firma disponible',
            'message'                   => 'Firma disponible'
        ];
    }
}
