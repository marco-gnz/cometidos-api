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
            /* ->where('status', true) */
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
            $fecha_by_solicitud = Carbon::parse($solicitud->fecha_last_firma)->format('Y-m-d');
            $primerFirmante = $solicitud->firmantes()
                ->whereJsonContains('permissions_id', $id_permission)
                ->where('solicitud_id', $solicitud->id)
                ->where(function ($query) use ($auth, $fecha_by_solicitud) {
                    $query->where(function ($q) use ($auth, $fecha_by_solicitud) {
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
                        });
                })
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

    private function obtenerPrimerFirmanteProcesoRendicionIsPermission($procesoRendicion, $id_permission)
    {
        $is_subrogante          = false;
        $is_firma               = false;
        $auth                   = auth()->user();
        $id_user_ejecuted_firma = null;

        $query = $procesoRendicion->solicitud->firmantes()
            /* ->where('status', true) */
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
            $fecha_by_p_rendicion = Carbon::parse($procesoRendicion->fecha_last_firma)->format('Y-m-d');
            $primerFirmante = $procesoRendicion->solicitud->firmantes()
                ->whereJsonContains('permissions_id', $id_permission)
                ->where('solicitud_id', $procesoRendicion->solicitud->id)
                ->where(function ($query) use ($auth, $fecha_by_p_rendicion) {
                    $query->where(function ($q) use ($auth, $fecha_by_p_rendicion) {
                        $q->whereHas('funcionario.reasignacionAusencias', function ($q) use ($auth, $fecha_by_p_rendicion) {
                            $q->where('user_subrogante_id', $auth->id);
                        });
                    })
                        ->orWhere(function ($q) use ($auth, $fecha_by_p_rendicion) {
                            $q->whereHas('funcionario.ausentismos', function ($q) use ($auth, $fecha_by_p_rendicion) {
                                $q->whereHas('subrogantes', function ($q) use ($auth, $fecha_by_p_rendicion) {
                                    $q->where('users.id', $auth->id);
                                })->where(function ($q)  use ($fecha_by_p_rendicion) {
                                    $q->where(function ($query) use ($fecha_by_p_rendicion) {
                                        $query->where('fecha_inicio', '<=', $fecha_by_p_rendicion)
                                            ->where('fecha_termino', '>=', $fecha_by_p_rendicion);
                                    })->orWhere(function ($query) use ($fecha_by_p_rendicion) {
                                        $query->where('fecha_inicio', '<=', $fecha_by_p_rendicion)
                                            ->where('fecha_termino', '>=', $fecha_by_p_rendicion);
                                    })->orWhere(function ($query) use ($fecha_by_p_rendicion) {
                                        $query->where('fecha_inicio', '>=', $fecha_by_p_rendicion)
                                            ->where('fecha_termino', '<=', $fecha_by_p_rendicion);
                                    });
                                });
                            });
                        });
                })
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
            $query->where('posicion_firma', $solicitud->posicion_firma_ok);
        } else {
            $query->where('posicion_firma', $solicitud->posicion_firma_ok);
        }

        return $query->first();
    }

    private function obtenerPrimerFirmanteHabilitadoProcesoRendicion($procesoRendicionGasto, $id_permission)
    {
        $query = $procesoRendicionGasto->solicitud->firmantes()
            ->whereJsonContains('permissions_id', $id_permission)
            /* ->where('status', true) */
            ->orderBy('posicion_firma', 'ASC');

        $query->where('posicion_firma', $procesoRendicionGasto->posicion_firma_ok);

        return $query->first();
    }

    public function obtenerFirmaDisponible($solicitud, $name_permission, $status = null)
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
            $title      = "{$name_user}, registras firma disponible especial.";
            $type       = 'warning';
            $is_firma   = true;
            $if_buttom              = true;
            $id_user_ejecuted_firma = $auth['id'];
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
                    $id_permission_informe_validar      = $this->idPermission('solicitud.informes.validar');
                    $get_last_calculo                   = $solicitud->getLastCalculo();
                    $informe_cometido                   = $solicitud->informeCometido();

                    if (($solicitud->derecho_pago) && in_array($id_permission_valorizacion_crear, $first_firma_auth->permissions_id) && !$get_last_calculo && isset($status) && $status === EstadoSolicitud::STATUS_APROBADO) {
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

                        $message_2 = null;
                        $status_informe_ok = [EstadoInformeCometido::STATUS_INGRESADA, EstadoInformeCometido::STATUS_MODIFICADO];
                        if (in_array($id_permission_informe_validar, $first_firma_auth->permissions_id) && ($informe_cometido) && (in_array($informe_cometido->last_status, $status_informe_ok))) {
                            $message_2 = 'Existe un Informe de Cometido pendiente por verificar. Al aprobar esta Solicitud de Cometido, el Informe de Cometido será aprobado automáticamente con su firma.';
                        }

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

                        if ($message_2) {
                            $message = "$message **$message_2**";
                        }
                    }
                } else {
                    $fecha_by_solicitud = Carbon::parse($solicitud->fecha_last_firma)->format('Y-m-d');
                    $first_firma_position = $solicitud->firmantes()
                        ->where(function ($q) use ($first_firma_habilitada_solicitud) {
                            $q->where('id', $first_firma_habilitada_solicitud->id);
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
                        ->where('is_executed', false)
                        ->where('solicitud_id', $solicitud->id)
                        ->first();

                    if ($first_firma_position) {
                        $name_user                          = $auth->abreNombres();
                        $if_buttom                          = true;
                        $get_last_calculo                   = $solicitud->getLastCalculo();
                        $id_permission_valorizacion_crear   = $this->idPermission('solicitud.valorizacion.crear');
                        $id_permission_informe_validar      = $this->idPermission('solicitud.informes.validar');
                        if (($solicitud->derecho_pago) && in_array($id_permission_valorizacion_crear, $first_firma_position->permissions_id) && !$get_last_calculo && isset($status) && $status === EstadoSolicitud::STATUS_APROBADO) {
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

                            $status_informe_ok = [EstadoInformeCometido::STATUS_INGRESADA, EstadoInformeCometido::STATUS_MODIFICADO];
                            $message_2          = null;
                            $informe_cometido   = $solicitud->informeCometido();
                            if (in_array($id_permission_informe_validar, $first_firma_position->permissions_id) && ($informe_cometido) && (in_array($informe_cometido->last_status, $status_informe_ok))) {
                                $message_2 = 'Existe un Informe de Cometido pendiente por verificar. Al aprobar esta Solicitud de Cometido, el Informe de Cometido será aprobado automáticamente con su firma.';
                            }

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

                            if ($message_2) {
                                $message = "$message **$message_2**";
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

    public function isFirmaDisponibleProcesoRendicionActionPolicy($procesoRendicion, $name_permission)
    {
        $id_permission = $this->idPermission($name_permission);
        if ($id_permission === null) {
            return (object) [
                'type'              => 'warning',
                'is_firma'          => false,
                'firma'             => null,
                'is_subrogante'     => false,
                'title'             => 'Firma no disponible',
                'message'           => 'Firma no disponible'
            ];

            return (object) [
                'type'                              => 'warning',
                'is_firma'                          => false,
                'firma'                             => null,
                'is_subrogante'                     => false,
                'if_buttom'                         =>  false,
                'id_firma'                          => null,
                'id_user_ejecuted_firma'            => null,
                'posicion_firma_proceso_rendicion'  => null,
                'posicion_firma'                    => null,
                'title'                             => 'Firma disponible',
                'message'                           => 'Firma disponible'
            ];
        }

        $first_firma_habilitada_p_rendicion = $this->obtenerPrimerFirmanteProcesoRendicionIsPermission($procesoRendicion, $id_permission);

        return (object) [
            'type'                              => 'success',
            'is_firma'                          => $first_firma_habilitada_p_rendicion->is_firma,
            'firma'                             => $first_firma_habilitada_p_rendicion->firma,
            'is_subrogante'                     => $first_firma_habilitada_p_rendicion->is_subrogante,
            'if_buttom'                         => $first_firma_habilitada_p_rendicion->is_firma ? true : false,
            'id_firma'                          => $first_firma_habilitada_p_rendicion->is_firma ? $first_firma_habilitada_p_rendicion->firma->id : null,
            'id_user_ejecuted_firma'            => $first_firma_habilitada_p_rendicion->is_firma ? $first_firma_habilitada_p_rendicion->id_user_ejecuted_firma : null,
            'posicion_firma_proceso_rendicion'  => $procesoRendicion->posicion_firma_actual,
            'posicion_firma'                    => $first_firma_habilitada_p_rendicion->is_firma ? $first_firma_habilitada_p_rendicion->firma->posicion_firma : null,
            'title'                             => 'Firma disponible',
            'message'                           => 'Firma disponible'
        ];
    }

    public function nextFirmaProcesoRendicion($before_or_after, $solicitud, $firma_disponible)
    {
        try {
            $permissions_rendiciones    = [24, 27, 25, 64];
            $comparison_operator        = $before_or_after;

            $posicion_firma = $firma_disponible->is_firma ? $firma_disponible->posicion_firma : 0;
            return $solicitud->firmantes()
                ->where('posicion_firma', $comparison_operator, $posicion_firma)
                ->where(function ($query) use ($permissions_rendiciones) {
                    foreach ($permissions_rendiciones as $permission_id) {
                        $query->orWhereJsonContains('permissions_id', $permission_id);
                    }
                })
                ->orderBy('posicion_firma', 'ASC')
                ->first();
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }

    public function obtenerFirmaDisponibleProcesoRendicion($procesoRendicionGasto, $name_permission, $status = null)
    {
        try {
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

            $id_permission                      = $this->idPermission($name_permission);
            $first_firma_habilitada_solicitud   = $this->obtenerPrimerFirmanteHabilitadoProcesoRendicion($procesoRendicionGasto, $id_permission);
            if ($first_firma_habilitada_solicitud) {
                $first_firma_auth = $procesoRendicionGasto->solicitud->firmantes()
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

                    $next_firma = $procesoRendicionGasto->solicitud->firmantes()
                        ->where('posicion_firma', '>', $first_firma_auth->posicion_firma)
                        ->orderBy('posicion_firma', 'ASC')
                        ->first();

                    if ($next_firma) {
                        $type       = 'success';
                        $title      = "{$name_user}, si registras firma disponible.";
                        $message    = "Al aprobar, la rendición se derivará a la firma N° {$next_firma->posicion_firma}, ejecutada por {$next_firma->funcionario->abreNombres()} - {$next_firma->perfil->name}.";
                    } else {
                        $type           = 'warning';
                        $title          = "{$name_user}, registras como último firmante.";
                        $message        = "Al aprobar finalizará el ciclo de firma.";
                    }
                } else {
                    $fecha_by_solicitud = Carbon::parse($procesoRendicionGasto->fecha_last_firma)->format('Y-m-d');
                    $first_firma_position = $procesoRendicionGasto->solicitud->firmantes()
                        ->where(function ($q) use ($first_firma_habilitada_solicitud) {
                            $q->where('id', $first_firma_habilitada_solicitud->id);
                        })
                        ->where(function ($query) use ($auth, $fecha_by_solicitud, $first_firma_habilitada_solicitud, $procesoRendicionGasto) {
                            $query->where(function ($q) use ($auth, $fecha_by_solicitud, $first_firma_habilitada_solicitud, $procesoRendicionGasto) {
                                $q->whereHas('funcionario.reasignacionAusencias', function ($q) use ($auth, $fecha_by_solicitud, $first_firma_habilitada_solicitud, $procesoRendicionGasto) {
                                    $q->where('user_subrogante_id', $auth->id)
                                        ->where('user_ausente_id', $first_firma_habilitada_solicitud->user_id)
                                        ->whereHas('solicitudes', function ($q)  use ($procesoRendicionGasto) {
                                            $q->where('solicituds.id', $procesoRendicionGasto->solicitud->id);
                                        });
                                });
                            })
                                ->orWhere(function ($q) use ($auth, $fecha_by_solicitud, $first_firma_habilitada_solicitud) {
                                    $q->whereHas('funcionario.ausentismos', function ($q) use ($auth, $fecha_by_solicitud, $first_firma_habilitada_solicitud) {
                                        $q->where('user_ausente_id', $first_firma_habilitada_solicitud->user_id)
                                            ->whereHas('subrogantes', function ($q) use ($auth, $fecha_by_solicitud) {
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
                                });
                        })
                        ->where('solicitud_id', $procesoRendicionGasto->solicitud->id)
                        ->orderBy('posicion_firma', 'ASC')
                        ->first();

                    if ($first_firma_position) {
                        $name_user                          = $auth->abreNombres();
                        $if_buttom                          = true;
                        $if_buttom              = false;
                        $is_firma               = true;
                        $id_firma               = $first_firma_position->id;
                        $id_user_ejecuted_firma = $auth->id;
                        $posicion_firma         = $first_firma_position->posicion_firma;
                        $is_subrogancia         = true;

                        $name_user  = $auth->abreNombres();
                        $next_firma = $procesoRendicionGasto->solicitud->firmantes()
                            ->where('posicion_firma', '>', $first_firma_position->posicion_firma)
                            ->orderBy('posicion_firma', 'ASC')
                            ->first();

                        if ($next_firma) {
                            $type       = 'warning';
                            $title      = "{$name_user}, registras firma disponible como subrogancia.";
                            $message    = "Al aprobar, la rendición se derivará a la firma N° {$next_firma->posicion_firma}, ejecutada por {$next_firma->funcionario->abreNombres()} - {$next_firma->perfil->name}.";
                        } else {
                            $type           = 'warning';
                            $title          = "{$name_user}, registras como último firmante y subrogante.";
                            $message        = "Al aprobar finalizará el ciclo de firma.";
                        }
                    } else {
                        $title      = 'No es posible aplicar verificación.';
                        $message    = "No registras firmas disponibles o no es el turno de firma.";
                        $type       = 'error';
                    }
                }
            } else {
                $title      = 'No es posible aplicar verificación.';
                $message    = "Rendición ya no registra firmas disponibles.";
                $type       = 'error';
            }


            $data = (object) [
                'title'                     => $title,
                'message'                   => $message,
                'type'                      => $type,
                'is_firma'                  => $is_firma,
                'if_buttom'                 => $if_buttom,
                'id_firma'                  => $id_firma,
                'id_user_ejecuted_firma'    => $id_user_ejecuted_firma,
                'posicion_firma_solicitud'  => $first_firma_habilitada_solicitud ? $first_firma_habilitada_solicitud->solicitud->posicion_firma_actual : 0,
                'posicion_firma_rendicion'  => $procesoRendicionGasto->posicion_firma_actual,
                'posicion_firma'            => $posicion_firma,
                'is_subrogante'             => $is_subrogancia,
                'role_id'                   => $first_firma_habilitada_solicitud ? $first_firma_habilitada_solicitud->role_id : null
            ];
            return $data;
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
    }
}
