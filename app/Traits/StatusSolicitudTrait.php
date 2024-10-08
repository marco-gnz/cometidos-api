<?php

namespace App\Traits;

use App\Models\CicloFirma;
use App\Models\EstadoProcesoRendicionGasto;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Traits\FirmaDisponibleTrait;
use Illuminate\Support\Facades\Log;

trait StatusSolicitudTrait
{
    use FirmaDisponibleTrait;

    public function navStatusRendicion($procesoRendicion)
    {
        $data_nav       = [];
        $permissions    = ['rendicion.firma.validar', 'rendicion.actividad.validar'];
        $firmantes      = $procesoRendicion->solicitud->firmantes()
            ->orderBy('posicion_firma', 'ASC')
            ->get();
        $firmantes_ok = [];
        $permissions_ok = [24, 27];
        foreach ($firmantes as $firmante) {
            if (in_array(24, $firmante->permissions_id) || in_array(27, $firmante->permissions_id)) {
                array_push($firmantes_ok, $firmante);
            }
        }
        foreach ($firmantes_ok as $firmante) {
            $last_estado = $procesoRendicion->estados()->where('role_id', $firmante->role_id)->orderBy('id', 'DESC')->first();
            $is_ciclo   = $last_estado ? ($last_estado->posicion_firma <= $procesoRendicion->posicion_firma_actual) : false;
            $type_nav               = 'light';
            $type_tag               = 'info';
            if ($is_ciclo) {
                switch ($last_estado->status) {
                    case 0:
                    case 1:
                        $type_nav = 'dark';
                        $type_tag = 'info';
                        break;

                    case 3:
                        $type_nav = 'warning';
                        $type_tag = 'warning';
                        break;

                    case 4:
                        $type_nav = 'success';
                        $type_tag = 'primary';
                        break;
                    case 2:
                    case 5:
                    case 6:
                        $type_nav = 'success';
                        $type_tag = 'success';
                        break;
                    case 7:
                    case 8:
                        $type_nav = 'danger';
                        $type_tag = 'danger';
                        break;
                }
            }
            $is_subrogante = false;

            $data       = (object) [
                'user_uuid'                         => $firmante->funcionario->uuid,
                'firmante_uuid'                     => $firmante->uuid,
                'nombres_firmante'                  => $is_ciclo ? $last_estado->userBy->abreNombres() : $firmante->funcionario->abreNombres(),
                'posicion_firma'                    => $firmante->posicion_firma,
                'perfil'                            => $firmante->perfil->name,
                'status_nom'                        => $is_ciclo ? EstadoProcesoRendicionGasto::STATUS_NOM[$last_estado->status] : EstadoProcesoRendicionGasto::STATUS_NOM[3],
                'status_value'                      => $is_ciclo ? $last_estado->status : null,
                'status_date'                       => $is_ciclo ? Carbon::parse($last_estado->fecha_by_user)->format('d-m-Y H:i:s') : null,
                'type_nav'                          => $type_nav,
                'type_tag'                          => $type_tag,
                'is_subrogancia'                    => $last_estado ? ($last_estado->is_subrogante ? true : false) : false
            ];
            array_push($data_nav, $data);
        }
        return $data_nav;
    }

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
            $reasignar_firma_value = $this->isReasignar($last_estado, $firmante, $solicitud);
            $nombres_firmante = null;
            if ($last_estado) {
                if ($is_reasignado) {
                    $nombres_firmante = $last_estado->funcionarioRs->abreNombres();
                } else {
                    $nombres_firmante = $last_estado->funcionario->abreNombres();
                }
            } else {
                $nombres_firmante = $firmante->funcionario->abreNombres();
            }
            $data       = (object) [
                'user_uuid'                         => $firmante->funcionario->uuid,
                'firmante_uuid'                     => $firmante->uuid,
                'nombres_firmante'                  => $nombres_firmante,
                'posicion_firma'                    => $firmante->posicion_firma,
                'perfil'                            => $firmante->perfil->name,
                'status_nom'                        => $is_ciclo ? EstadoSolicitud::STATUS_NOM[$last_estado->status] : EstadoSolicitud::STATUS_NOM[1],
                'status_value'                      => $is_ciclo ? $last_estado->status : null,
                'status_date'                       => $is_ciclo ? Carbon::parse($last_estado->created_at)->format('d-m-Y H:i:s') : null,
                'firma_is_reasignado'               => $is_reasignado,
                'type_nav'                          => $type_nav,
                'type_tag'                          => $type_tag,
                'is_firma'                          => $last_estado ? ($last_estado->posicion_firma <= $solicitud->posicion_firma_actual) : false,
                'reasignar_firma_value'             => $reasignar_firma_value,
                'is_subrogancia'                    => $last_estado ? ($last_estado->is_subrogante ? true : false) : false
            ];
            array_push($data_nav, $data);
        }
        return $data_nav;
    }

    private function isReasignar($last_estado, $firmante,  $solicitud)
    {
        $id_permission_valorizacion_crear   = $this->idPermission('solicitud.valorizacion.crear');
        $id_permission_ajustes_crear        = $this->idPermission('solicitud.ajustes.crear');
        $ids_permissions = [
            $id_permission_valorizacion_crear,
            $id_permission_ajustes_crear
        ];
        $auth       = auth()->user();

        if ($firmante->posicion_firma !== 0) {
            $filtered_permissions_id = array_filter($firmante->permissions_id, function ($value) {
                return $value !== null;
            });

            $intersection           = array_intersect($ids_permissions, $filtered_permissions_id);
            $al_menos_uno_presente  = !empty($intersection);
            $reasginar              = $last_estado ? (($auth->id !== $firmante->user_id) && ($al_menos_uno_presente) && ($firmante->status) && ($last_estado->posicion_firma <= $solicitud->posicion_firma_actual && !$firmante->is_reasignado) ? true : false) : false;
            return $reasginar;
        } else {
            $reasginar  = $last_estado ? (($auth->id !== $firmante->user_id) && ($firmante->status) && ($last_estado->posicion_firma <= $solicitud->posicion_firma_actual && !$firmante->is_reasignado) ? true : false) : false;
            return $reasginar;
        }
    }

    private function getPermissions($role_id, $solicitud)
    {
        $ciclo_firma = CicloFirma::where('establecimiento_id', $solicitud->establecimiento_id)
            ->where('role_id', $role_id)
            ->first();

        if (!$ciclo_firma) {
            return null;
        }

        return $ciclo_firma->permissions()->pluck('permission_id')->toArray();
    }

    public function updatePosicionSolicitud($solicitud)
    {
        try {
            if ($solicitud) {
                $last_status        = $solicitud->estados()->orderBy('id', 'DESC')->first();
                $isFirmaPendiente   = $solicitud->isFirmaPendiente();
                $solicitud->update([
                    'fecha_last_firma'  => $last_status ? $last_status->created_at : $solicitud->fecha_last_firma,
                    'posicion_firma_ok' => $isFirmaPendiente ? $isFirmaPendiente->posicion_firma : $solicitud->posicion_firma_ok
                ]);
            }
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }
}
