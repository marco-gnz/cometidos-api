<?php

namespace App\Traits;

use App\Models\EstadoInformeCometido;
use App\Models\EstadoProcesoRendicionGasto;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

trait FirmaDisponibleTrait
{
    public function obtenerFirmaDisponible($solicitud)
    {
        $auth = Auth::user();
        if ($solicitud->status === Solicitud::STATUS_PROCESADO) {
            if ($solicitud->authorizedToReasignarEmergency()) {
                $name_user          = $auth->abreNombres();
                $type               = 'warning';
                $title              = "{$name_user}, registras firma disponible de emergencia.";
                $is_firma           = true;
                $first_firma_auth   = null;
                $data = (object) [
                    'is_firma'                  => $is_firma,
                    'title'                     => $title,
                    'message'                   => null,
                    'posicion_firma_solicitud'  => null,
                    'id_firma'                  => ($is_firma) && $first_firma_auth ? $first_firma_auth->id : null,
                    'posicion_firma'            => ($is_firma) && $first_firma_auth ? $first_firma_auth->posicion_firma : null,
                    'type'                      => $type
                ];
                return $data;
            }
        }
        $name_roles = ['EJECUTIVO', 'JEFE DIRECTO', 'JEFE PERSONAL', 'SUB DIRECTOR', 'REVISOR FINANZAS', 'JEFE FINANZAS'];
        $roles_id = Role::whereIn('name', $name_roles)->pluck('id')->toArray();
        if (!$solicitud->is_reasignada) {
            $first_firma_habilitada_solicitud = $solicitud->firmantes()->whereIn('role_id', $roles_id)->where('status', true)->where('posicion_firma', '>', $solicitud->posicion_firma_actual)->orderBy('posicion_firma', 'ASC')->first();
        } else {
            $first_firma_habilitada_solicitud = $solicitud->firmantes()->whereIn('role_id', $roles_id)->where('status', true)->where('posicion_firma', $solicitud->posicion_firma_actual)->orderBy('posicion_firma', 'ASC')->first();
        }

        if ($first_firma_habilitada_solicitud) {
            $first_firma_auth = $solicitud->firmantes()->whereIn('role_id', $roles_id)->where('status', true)->where('user_id', $auth->id)->where('id', $first_firma_habilitada_solicitud->id)->first();
            if ($first_firma_auth) {
                $is_firma           = true;
                $next_firma         = $solicitud->firmantes()->whereIn('role_id', $roles_id)->where('status', true)->where('posicion_firma', '>', $first_firma_auth->posicion_firma)->orderBy('posicion_firma', 'ASC')->first();
                $name_user          = $first_firma_auth->funcionario->abreNombres();

                $get_last_calculo = $solicitud->getLastCalculo();
                if (!$get_last_calculo && $first_firma_auth->role_id === 2) {
                    $is_firma           = false;
                    $type               = 'success';
                    $title              = "{$name_user}, si registras firma disponible, pero existen tareas por ejecutar.";
                    $message            = "Una vez ejecutadas las tareas será posible continuar con ciclo de firma.";
                }
                if ($next_firma) {
                    $type               = 'success';
                    $title              = "{$name_user}, si registras firma disponible.";
                    $message            = "Al aprobar, solicitud se derivará a firma N° {$next_firma->posicion_firma}, ejecutada por {$next_firma->funcionario->nombre_completo} - {$next_firma->perfil->name}.";
                } else {
                    $type               = 'warning';
                    $title              = "{$name_user}, registras como último firmante.";
                    $estado_finish      = Solicitud::STATUS_NOM[Solicitud::STATUS_PROCESADO];
                    $message            = "Al aprobar finalizará el ciclo de firma y la solicitud será {$estado_finish}";
                }
            } else {
                $is_firma           = false;
                $title              = 'No es posible aplicar verificación.';
                $message            = "No registras firmas disponibles o no es el turno de firma.";
                $type               = 'error';
            }
        } else {
            $is_firma           = false;
            $title              = 'No es posible aplicar verificación.';
            $message            = "Solicitud ya no registra firmas disponibles.";
            $type               = 'error';
        }
        $data = (object) [
            'is_firma'                  => $is_firma,
            'title'                     => $title,
            'message'                   => $message,
            'posicion_firma_solicitud'  => $solicitud->posicion_firma_actual,
            'id_firma'                  => ($is_firma) && $first_firma_auth ? $first_firma_auth->id : null,
            'posicion_firma'            => ($is_firma) && $first_firma_auth ? $first_firma_auth->posicion_firma : null,
            'type'                      => $type
        ];

        return $data;
    }

    public function obtenerFirmaDisponibleSolicitudAnular($solicitud)
    {
        $auth   = Auth::user();

        if ($solicitud->status === Solicitud::STATUS_ANULADO) {
            return (object) [
                'type'      => 'warning',
                'is_firma'  => false,
                'firma'     => null,
                'title'     => 'Firma no disponible',
                'message'   => 'Firma no disponible'
            ];
        }
        $roles_id   = [1, 2];
        $firma      = $solicitud->firmantes()->where('user_id', $auth->id)->where('status', true)->whereIn('role_id', $roles_id)->first();

        $title = $firma ? 'Si registras firma para anular.' : 'No registras firma para anular.';
        return (object) [
            'type'                      => 'success',
            'title'                     => $title,
            'message'                   => null,
            'is_firma'                  => $firma ? true : false,
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

    public function obtenerFirmaDisponibleCalculo($solicitud)
    {
        $auth = Auth::user();
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

        $roles_id    = [2];
        $firma       = $solicitud->firmantes()->where('user_id', $auth->id)->where('status', true)->whereIn('role_id', $roles_id)->first();

        return (object) [
            'type'      => 'success',
            'is_firma'  => $firma ? true : false,
            'firma'     => $firma,
            'title'     => null,
            'message'   => null
        ];
    }
}
