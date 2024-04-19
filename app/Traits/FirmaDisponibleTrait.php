<?php

namespace App\Traits;

use App\Models\EstadoInformeCometido;
use App\Models\EstadoProcesoRendicionGasto;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
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
        $query = $solicitud->firmantes()
            ->where('status', true)
            ->where('user_id', auth()->user()->id)
            ->whereJsonContains('permissions_id', $id_permission)
            ->orderBy('posicion_firma', 'ASC')
            ->first();


        return $query;
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
        $auth       = Auth::user();
        $is_firma   = false;
        $message    = null;

        if ($solicitud->status === Solicitud::STATUS_PROCESADO && $solicitud->authorizedToReasignarEmergency()) {
            $name_user  = $auth->abreNombres();
            $title      = "{$name_user}, registras firma disponible de emergencia.";
            $type       = 'warning';
            $is_firma   = true;
        } else {
            $id_permission = $this->idPermission($name_permission);

            $first_firma_habilitada_solicitud = $this->obtenerPrimerFirmanteHabilitado($solicitud, $id_permission);

            if ($first_firma_habilitada_solicitud) {
                $first_firma_auth = $solicitud->firmantes()->where('user_id', $auth->id)
                    ->where('id', $first_firma_habilitada_solicitud->id)
                    ->where('status', true)
                    ->first();

                if ($first_firma_auth) {
                    $is_firma                           = true;
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
                    $title      = 'No es posible aplicar verificación.';
                    $message    = "No registras firmas disponibles o no es el turno de firma.";
                    $type       = 'error';
                }
            } else {
                $title      = 'No es posible aplicar verificación.';
                $message    = "Solicitud ya no registra firmas disponibles.";
                $type       = 'error';
            }
        }

        $data = (object) [
            'is_firma'                  => $is_firma,
            'title'                     => $title ?? null,
            'message'                   => $message,
            'posicion_firma_solicitud'  => $solicitud->posicion_firma_actual,
            'id_firma'                  => ($is_firma && isset($first_firma_auth)) ? $first_firma_auth->id : null,
            'posicion_firma'            => ($is_firma && isset($first_firma_auth)) ? $first_firma_auth->posicion_firma : null,
            'type'                      => $type ?? null
        ];

        return $data;
    }

    public function isFirmaDisponibleAction($solicitud, $name_permission)
    {
        $id_permission = $this->idPermission($name_permission);
        if ($id_permission === null) {
            return (object) [
                'type'      => 'warning',
                'is_firma'  => false,
                'firma'     => null,
                'title'     => 'Firma no disponible',
                'message'   => 'Firma no disponible'
            ];
        }

        $first_firma_habilitada_solicitud = $this->obtenerPrimerFirmanteHabilitado($solicitud, $id_permission);

        return (object) [
            'type'      => 'success',
            'is_firma'  => $first_firma_habilitada_solicitud ? true : false,
            'firma'     => $first_firma_habilitada_solicitud,
            'title'     => 'Firma disponible',
            'message'   => 'Firma disponible'
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
                'title'     => 'Firma no disponible',
                'message'   => 'Firma no disponible'
            ];
        }

        $first_firma_habilitada_solicitud = $this->obtenerPrimerFirmanteIsPermission($solicitud, $id_permission);

        return (object) [
            'type'      => 'success',
            'is_firma'  => $first_firma_habilitada_solicitud ? true : false,
            'firma'     => $first_firma_habilitada_solicitud,
            'title'     => 'Firma disponible',
            'message'   => 'Firma disponible'
        ];
    }



    public function obtenerFirmaDisponibleSolicitudAnular($solicitud, $name_permission)
    {
        $auth   = Auth::user();
        $status = $solicitud->status;
        if ($status === EstadoSolicitud::STATUS_ANULADO) {
            return (object) [
                'type'      => 'warning',
                'is_firma'  => false,
                'firma'     => null,
                'title'     => 'Firma no disponible',
                'message'   => 'Firma no disponible'
            ];
        }

        $id_permission = $this->idPermission($name_permission);
        if ($id_permission === null) {
            return (object) [
                'type'      => 'success',
                'is_firma'  => false,
                'firma'     => null,
                'title'     => null,
                'message'   => null
            ];
        }

        $firma = $solicitud->firmantes()
            ->where('user_id', $auth->id)
            ->where('status', true)
            ->whereJsonContains('permissions_id', $id_permission)
            ->first();

        $is_firma   = $firma !== null;
        $title      = $firma ? 'Si registras firma para anular.' : 'No registras firma para anular.';
        return (object) [
            'type'                      => 'success',
            'title'                     => $title,
            'message'                   => null,
            'is_firma'                  => $is_firma,
            'firma'                     => $firma,
            'posicion_firma_solicitud'  => $solicitud->posicion_firma_actual,
            'posicion_firma'            => $firma ? $firma->posicion_firma : null,
            'id_firma'                  => $firma ? $firma->id : null,
        ];
    }

    public function obtenerFirmaDisponibleProcesoRendicion($procesoRendicion)
    {
        $auth   = Auth::user();
        $status = $procesoRendicion->status;

        if ($status === EstadoProcesoRendicionGasto::STATUS_ANULADO) {
            return (object) [
                'type'      => 'warning',
                'is_firma'  => false,
                'firma'     => null,
                'title'     => 'Firma no disponible',
                'message'   => 'Firma no disponible'
            ];
        }

        $firma      = null;
        $role_id    = null;

        if ($status === EstadoProcesoRendicionGasto::STATUS_INGRESADA || $status === EstadoProcesoRendicionGasto::STATUS_MODIFICADA) {
            $role_id = 3;
        } elseif ($status === EstadoProcesoRendicionGasto::STATUS_VERIFICADO) {
            $role_id = 7;
        }

        $firma = $procesoRendicion->solicitud->firmantes()->where('user_id', $auth->id)->where('status', true)->where('role_id', $role_id)->first();

        return (object) [
            'type'      => 'success',
            'is_firma'  => $firma ? true : false,
            'firma'     => $firma,
            'title'     => null,
            'message'   => null
        ];
    }

    public function obtenerFirmaDisponibleProcesoRendicionPago($procesoRendicion)
    {
        $auth   = Auth::user();
        $status = $procesoRendicion->status;

        if ($status === EstadoProcesoRendicionGasto::STATUS_ANULADO) {
            return (object) [
                'type'      => 'warning',
                'is_firma'  => false,
                'firma'     => null,
                'title'     => 'Firma no disponible',
                'message'   => 'Firma no disponible'
            ];
        }

        $roles_id = [6, 7];

        $firma = $procesoRendicion->solicitud->firmantes()->where('user_id', $auth->id)->where('status', true)->whereIn('role_id', $roles_id)->first();

        return (object) [
            'type'      => 'success',
            'is_firma'  => $firma ? true : false,
            'firma'     => $firma,
            'title'     => null,
            'message'   => null
        ];
    }

    public function obtenerFirmaDisponibleProcesoRendicionAnular($procesoRendicion)
    {
        $auth   = Auth::user();
        $status = $procesoRendicion->status;

        if ($status === EstadoProcesoRendicionGasto::STATUS_ANULADO) {
            return (object) [
                'type'      => 'warning',
                'is_firma'  => false,
                'firma'     => null,
                'title'     => 'Firma no disponible',
                'message'   => 'Firma no disponible'
            ];
        }
        $roles_id   = [1, 3, 6, 7];
        $firma      = $procesoRendicion->solicitud->firmantes()->where('user_id', $auth->id)->where('status', true)->whereIn('role_id', $roles_id)->first();

        return (object) [
            'type'      => 'success',
            'is_firma'  => $firma ? true : false,
            'firma'     => $firma,
            'title'     => null,
            'message'   => null
        ];
    }

    public function obtenerFirmaDisponibleRendicion($procesoRendicion)
    {
        $auth = Auth::user();
        $status = $procesoRendicion->status;

        if ($status === EstadoProcesoRendicionGasto::STATUS_ANULADO) {
            return (object) [
                'type'      => 'warning',
                'is_firma'  => false,
                'firma'     => null,
                'title'     => 'Firma no disponible',
                'message'   => 'Firma no disponible'
            ];
        }

        $roles_id    = [6, 7];
        $firma       = $procesoRendicion->solicitud->firmantes()->where('user_id', $auth->id)->where('status', true)->whereIn('role_id', $roles_id)->first();

        return (object) [
            'type'      => 'success',
            'is_firma'  => $firma ? true : false,
            'firma'     => $firma,
            'title'     => null,
            'message'   => null
        ];
    }

    public function obtenerFirmaDisponibleInformeCometido($informeCometido)
    {
        $auth        = Auth::user();
        $roles_id    = [4];
        $firma       = $informeCometido->solicitud->firmantes()->where('user_id', $auth->id)->where('status', true)->whereIn('role_id', $roles_id)->first();

        return (object) [
            'type'      => 'success',
            'is_firma'  => $firma ? true : false,
            'firma'     => $firma,
            'title'     => null,
            'message'   => null
        ];
    }

    public function obtenerFirmaDisponibleCalculo($solicitud, $name_permission)
    {
        $auth   = Auth::user();
        $status = $solicitud->status;

        if ($status === EstadoSolicitud::STATUS_ANULADO) {
            return (object) [
                'type'      => 'warning',
                'is_firma'  => false,
                'firma'     => null,
                'title'     => 'Firma no disponible',
                'message'   => 'Firma no disponible'
            ];
        }

        $id_permission = $this->idPermission($name_permission);
        if ($id_permission === null) {
            return (object) [
                'type'      => 'success',
                'is_firma'  => false,
                'firma'     => null,
                'title'     => null,
                'message'   => null
            ];
        }

        $firma = $solicitud->firmantes()
            ->where('user_id', $auth->id)
            ->where('status', true)
            ->whereJsonContains('permissions_id', $id_permission)
            ->first();

        $is_firma = $firma !== null;

        return (object) [
            'type'      => 'success',
            'is_firma'  => $is_firma,
            'firma'     => $firma,
            'title'     => null,
            'message'   => null
        ];
    }

    public function obtenerFirmaDisponibleConvenio($solicitud, $name_permission)
    {
        $auth   = Auth::user();
        $status = $solicitud->status;

        if ($status === EstadoSolicitud::STATUS_ANULADO) {
            return (object) [
                'type'      => 'warning',
                'is_firma'  => false,
                'firma'     => null,
                'title'     => 'Firma no disponible',
                'message'   => 'Firma no disponible'
            ];
        }

        $id_permission = $this->idPermission($name_permission);
        if ($id_permission === null) {
            return (object) [
                'type'      => 'success',
                'is_firma'  => false,
                'firma'     => null,
                'title'     => null,
                'message'   => null
            ];
        }

        $firma = $solicitud->firmantes()
            ->where('user_id', $auth->id)
            ->where('status', true)
            ->whereJsonContains('permissions_id', $id_permission)
            ->first();

        $is_firma = $firma !== null;

        return (object) [
            'type'      => 'success',
            'is_firma'  => $is_firma,
            'firma'     => $firma,
            'title'     => null,
            'message'   => null
        ];
    }

    public function obtenerFirmaDisponibleEdit($solicitud, $name_permission)
    {
        $auth   = Auth::user();
        $status = $solicitud->status;

        if ($status === EstadoSolicitud::STATUS_ANULADO) {
            return (object) [
                'type'      => 'warning',
                'is_firma'  => false,
                'firma'     => null,
                'title'     => 'Firma no disponible',
                'message'   => 'Firma no disponible'
            ];
        }

        $id_permission = $this->idPermission($name_permission);
        if ($id_permission === null) {
            return (object) [
                'type'      => 'success',
                'is_firma'  => false,
                'firma'     => null,
                'title'     => null,
                'message'   => null
            ];
        }

        $firma = $solicitud->firmantes()
            ->where('user_id', $auth->id)
            ->where('status', true)
            ->whereJsonContains('permissions_id', $id_permission)
            ->first();

        $is_firma = $firma !== null;

        return (object) [
            'type'      => 'success',
            'is_firma'  => $is_firma,
            'firma'     => $firma,
            'title'     => null,
            'message'   => null
        ];
    }
}
