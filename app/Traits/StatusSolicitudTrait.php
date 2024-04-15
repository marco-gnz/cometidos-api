<?php

namespace App\Traits;

use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

trait StatusSolicitudTrait
{
    public function navStatusSolicitud($solicitud)
    {
        $data_nav   = [];
        $firmantes  = $solicitud->firmantes()->where('status', true)->orderBy('posicion_firma', 'ASC')->get();
        foreach ($firmantes as $firmante) {
            $last_estado            = $firmante->estados()->where('solicitud_id', $solicitud->id)->orderBy('id', 'DESC')->first();

            $is_reasignado = $firmante->is_reasignado && $solicitud->posicion_firma_actual === $last_estado->posicion_firma ? true : false;
            if ($is_reasignado) {
                $type_nav               = 'warning';
                $type_tag               = 'warning';
            } else {
                $type_nav               = 'light';
                $type_tag               = 'info';
            }


            $is_ciclo   = $last_estado ? ($last_estado->posicion_firma <= $solicitud->posicion_firma_actual && !$firmante->is_reasignado ? true : false) : false;
            if ($is_ciclo) {
                switch ($last_estado->status) {
                    case 1:
                    case 5:
                        $type_nav = 'dark';
                        $type_tag = 'info';
                        break;

                    case 0:
                    case 2:
                        $type_nav = 'success';
                        $type_tag = 'success';
                        break;

                    case 3:
                    case 4:
                        $type_nav = 'danger';
                        $type_tag = 'danger';
                        break;
                }
            }
            $data       = (object) [
                'user_uuid'                         => $firmante->funcionario->uuid,
                'firmante_uuid'                     => $firmante->uuid,
                'nombres_firmante'                  => $firmante->funcionario->abreNombres(),
                'posicion_firma'                    => $firmante->posicion_firma,
                'perfil'                            => $firmante->perfil->name,
                'status_nom'                        => $is_ciclo ? EstadoSolicitud::STATUS_NOM[$last_estado->status] : EstadoSolicitud::STATUS_NOM[1],
                'status_value'                      => $is_ciclo ? $last_estado->status : null,
                'status_date'                       => $is_ciclo ? Carbon::parse($last_estado->created_at)->format('d-m-Y H:i:s') : null,
                'firma_is_reasignado'               => $is_reasignado,
                'type_nav'                          => $type_nav,
                'type_tag'                          => $type_tag,
                'is_firma'                          => $last_estado ? ($last_estado->posicion_firma <= $solicitud->posicion_firma_actual) : false,
                'reasignar_firma_value'             => $this->isReasignar($last_estado, $firmante, $solicitud)
            ];
            array_push($data_nav, $data);
        }
        return $data_nav;
    }

    private function isReasignar($last_estado, $firmante,  $solicitud)
    {
        $auth = auth()->user();
        $reasginar  = $last_estado ? (($auth->id !== $firmante->user_id) && ($firmante->role_id === 1 || $firmante->role_id === 2) && ($firmante->status) && ($last_estado->posicion_firma <= $solicitud->posicion_firma_actual && !$firmante->is_reasignado) ? true : false) : false;
        return $reasginar;
    }
}
