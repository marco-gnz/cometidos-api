<?php

namespace App\Traits;

use App\Models\Solicitud;
use Illuminate\Support\Facades\Auth;

trait FirmaDisponibleTrait
{
    public function obtenerFirmaDisponible($solicitud, $status)
    {
        $auth = Auth::user();

        if ($status !== 4) {
            if (!$solicitud->is_reasignada) {
                $first_firma_habilitada_solicitud = $solicitud->firmantes()->where('status', true)->where('posicion_firma', '>', $solicitud->posicion_firma_actual)->orderBy('posicion_firma', 'ASC')->first();
            } else {
                $first_firma_habilitada_solicitud = $solicitud->firmantes()->where('status', true)->where('posicion_firma', $solicitud->posicion_firma_actual)->orderBy('posicion_firma', 'ASC')->first();
            }

            if ($first_firma_habilitada_solicitud) {
                $first_firma_auth = $solicitud->firmantes()->where('status', true)->where('user_id', $auth->id)->where('id', $first_firma_habilitada_solicitud->id)->first();
                if ($first_firma_auth) {
                    $is_firma           = true;
                    $next_firma         = $solicitud->firmantes()->where('status', true)->where('posicion_firma', '>', $first_firma_auth->posicion_firma)->orderBy('posicion_firma', 'ASC')->first();
                    $name_user          = $first_firma_auth->funcionario->abreNombres();
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
        } else {
            $firma_x            = $solicitud->firmantes()->where('user_id', $auth->id)->whereIn('role_id', [1, 2])->where('status', true)->first();
            $type               = 'warning';
            $is_firma           = false;
            if ($firma_x) {
                $name_user          = $firma_x->funcionario->abreNombres();
                $is_firma           = true;
                $title              = "{$name_user}, si registras firma disponible.";
                $message            = "Al anular, finalizará el ciclo de firma y la solicitud será anulada. No es posible revocar esto.";
            } else {
                $title              = 'No es posible aplicar verificación.';
                $message            = "No registras firmas disponibles para ANULAR solicitud.";
                $type               = 'error';
            }
            $data = (object) [
                'is_firma'                  => $firma_x ? true : false,
                'title'                     => $title,
                'message'                   => $message,
                'posicion_firma_solicitud'  => $solicitud->posicion_firma_actual,
                'id_firma'                  => $firma_x ? $firma_x->id : null,
                'posicion_firma'            => $firma_x ? $firma_x->posicion_firma : null,
                'type'                      => $type
            ];
        }
        return $data;
    }
}
