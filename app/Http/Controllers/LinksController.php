<?php

namespace App\Http\Controllers;

use App\Models\Ausentismo;
use App\Models\EstadoInformeCometido;
use App\Models\EstadoProcesoRendicionGasto;
use Illuminate\Http\Request;
use App\Models\Link;
use App\Models\ProcesoRendicionGasto;
use App\Models\Solicitud;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LinksController extends Controller
{
    public function getLinks()
    {
        $user = Auth::user();

        $is_show_solicitud = false;
        $is_show_rendicion = false;

        $in_grupo_firma = $user->firmasGrupo()->where('status', true)->count();

        $firmas = $this->totalCometidosPendientes($user);
        $firmas_rendiciones = $this->totalRendicionesPendientes($user);
        $informes_pendientes = $this->totalInformesPendientes($user);

        $status = [
            EstadoProcesoRendicionGasto::STATUS_ANULADO,
            EstadoProcesoRendicionGasto::STATUS_APROBADO_S,
            EstadoProcesoRendicionGasto::STATUS_APROBADO_N,
            EstadoProcesoRendicionGasto::STATUS_RECHAZADO
        ];

        $reasignaciones = $user->reasignacionAsignadas()->count();
        $subrogancias = Ausentismo::whereHas('subrogantes', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })->count();
        if ($in_grupo_firma > 0 || $subrogancias > 0 || $reasignaciones > 0 || $user->hasRole('SUPER ADMINISTRADOR') || $user->hasPermissionTo('solicitudes.ver')) {
            $is_show_solicitud = true;
        }

        if ($in_grupo_firma > 0 || $subrogancias > 0 || $reasignaciones > 0 || $user->hasRole('SUPER ADMINISTRADOR') || $user->hasPermissionTo('rendiciones.ver')) {
            $is_show_rendicion = true;
        }

        $linksUsers = [];
        $linksAdmin = [];

        if ($is_show_solicitud) {
            $linksAdmin[] = Link::create(
                "list-solicitudes",
                "Cometidos pendientes de firma",
                "Firma de cometidos",
                "/firmante/solicitudes",
                "d93c47",
                false,
                $firmas
            );

            $linksAdmin[] = Link::create(
                "list-informes",
                "Informes de cometido pendiente de firma",
                "Firma de informes de cometido",
                "/firmante/solicitudes",
                "fdc109",
                false,
                $informes_pendientes
            );
        }

        if ($is_show_rendicion) {
            $linksAdmin[] = Link::create(
                "list-rendicion-gastos",
                "Rendiciones de gasto pendientes de firma",
                "Firma de rendición de gastos",
                "/firmante/rendiciones",
                "0e6db8",
                false,
                $firmas_rendiciones
            );
        }

        $linksUsers = [];
        if ($user->hasPermissionTo('grupofirma.ver')) {
            $linksUsers[] = Link::create(
                "list-grupos",
                "Grupos de firma",
                "Listado de grupos de firma",
                "/admin/grupos",
                "0e6db8",
                false,
                null
            );
        }
        if ($user->hasPermissionTo('convenio.ver')) {
            $linksUsers[] = Link::create(
                "list-convenios",
                "Convenios",
                "Convenios de cometido",
                "/admin/convenios",
                "0e6db8",
                false,
                null
            );
        }
        if ($user->hasPermissionTo('ausentismo.ver')) {
            $linksUsers[] = Link::create(
                "list-ausentismos",
                "Ausentismos",
                "Listado de ausentismos",
                "/admin/ausentismos",
                "0e6db8",
                false,
                null
            );
        }
        if ($user->hasPermissionTo('reasignacion.ver')) {
            $linksUsers[] = Link::create(
                "list-reasignaciones",
                "Reasignaciones",
                "Listado de reasignaciones",
                "/admin/reasignaciones",
                "0e6db8",
                false,
                null
            );
        }
        if ($user->hasPermissionTo('funcionario.ver')) {
            $linksUsers[] = Link::create(
                "list-funcionarios",
                "Funcionarios",
                "Listado de funcionarios",
                "/admin/funcionarios",
                "0e6db8",
                false,
                null
            );
        }
        if ($user->hasPermissionTo('usuarioespecial.ver')) {
            $linksUsers[] = Link::create(
                "list-usuarios-especiales",
                "Usuarios especiales",
                "Usuarios especiales",
                "/admin/usuarios-especiales",
                "0e6db8",
                false,
                null
            );
        }
        if ($user->hasPermissionTo('configuracion.ver')) {
            $linksUsers[] = Link::create(
                "list-configuraciones",
                "Configuraciones",
                "Listado de configuraciones",
                "/admin/configuraciones",
                "0e6db8",
                false,
                null
            );
        }
        if ($user->hasPermissionTo('perfil.ver')) {
            $linksUsers[] = Link::create(
                "list-admin",
                "Perfiles",
                "Listado de usuarios con perfiles",
                "/admin/perfiles",
                "0e6db8",
                false,
                null
            );
        }

        if ($user->hasPermissionTo('configuracion.ver')) {
            $linksUsers[] = Link::create(
                "list-lugares",
                "Lugares",
                "Listado de lugares de cometido",
                "/admin/otros/lugares",
                "0e6db8",
                false,
                null
            );
        }

        if ($user->hasPermissionTo('configuracion.ver')) {
            $linksUsers[] = Link::create(
                "list-lugares",
                "Motivos",
                "Listado de motivos de cometido",
                "/admin/otros/motivos",
                "0e6db8",
                false,
                null
            );
        }

        if ($user->hasPermissionTo('reporte.solicitud') || $user->hasPermissionTo('reporte.rendicion')) {
            if ($user->hasPermissionTo('reporte.rendicion')) {
                $type = 'rendicion';
            }

            if ($user->hasPermissionTo('reporte.solicitud')) {
                $type = 'solicitud';
            }
            $linksUsers[] = Link::create(
                "list-reportes",
                "Reportes",
                "Listado de reportes",
                "/admin/reportes?type={$type}",
                "0e6db8",
                false,
                null
            );
        }

        return response()->json([
            'links_admin' => $linksAdmin,
            'links_users' => $linksUsers,
        ]);
    }

    private function totalCometidosPendientes($auth)
    {
        return Solicitud::where(function ($q) use ($auth) {
            $q->whereHas('firmantes', function ($q) use ($auth) {
                $q->where(function ($q) use ($auth) {
                    $q->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma - 1')
                        ->where('solicituds.is_reasignada', 0)
                        ->where('status', true)
                        ->where('is_executed', false)
                        ->where('role_id', '!=', 1)
                        ->where('user_id', $auth->id)
                        ->where('solicituds.status', '=', Solicitud::STATUS_EN_PROCESO);
                })
                    ->orWhere(function ($q) use ($auth) {
                        $q->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma')
                            ->where('solicituds.is_reasignada', 1)
                            ->where('is_reasignado', true)
                            ->where('status', true)
                            ->where('is_executed', false)
                            ->where('role_id', '!=', 1)
                            ->where('user_id', $auth->id)
                            ->where('solicituds.status', '=', Solicitud::STATUS_EN_PROCESO);
                    });
            });
        })->orWhere(function ($q) use ($auth) {
            $q->whereHas('firmantes', function ($q) use ($auth) {
                $q->whereHas('funcionario.ausentismos', function ($q) use ($auth) {
                    $q->where(function ($query) use ($auth) {
                        $query
                            ->whereRaw("DATE(solicituds.fecha_by_user) >= ausentismos.fecha_inicio")
                            ->whereRaw("DATE(solicituds.fecha_by_user) <= ausentismos.fecha_termino")
                            ->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma - 1')
                            ->where('solicituds.is_reasignada', 0)
                            ->where('status', true)
                            ->where('is_executed', false)
                            ->where('role_id', '!=', 1)
                            ->where('solicituds.status', '=', Solicitud::STATUS_EN_PROCESO)
                            ->whereHas('subrogantes', function ($q) use ($auth) {
                                $q->where('users.id', $auth->id);
                            });
                    })->orWhere(function ($query) use ($auth) {
                        $query
                            ->whereRaw("DATE(solicituds.fecha_by_user) >= ausentismos.fecha_inicio")
                            ->whereRaw("DATE(solicituds.fecha_by_user) <= ausentismos.fecha_termino")
                            ->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma')
                            ->where('solicituds.is_reasignada', 1)
                            ->where('is_reasignado', true)
                            ->where('status', true)
                            ->where('is_executed', false)
                            ->where('role_id', '!=', 1)
                            ->where('solicituds.status', '=', Solicitud::STATUS_EN_PROCESO)
                            ->whereHas('subrogantes', function ($q) use ($auth) {
                                $q->where('users.id', $auth->id);
                            });
                    });
                });
            });
        })->orWhere(function ($q) use ($auth) {
            $q->whereHas('firmantes', function ($q) use ($auth) {
                $q->where('is_executed', false)
                    ->whereHas('funcionario.reasignacionAusencias', function ($q) use ($auth) {
                        $q->where('user_subrogante_id', $auth->id)
                            ->where(function ($query) {
                                $query->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma - 1')
                                    ->where('solicituds.is_reasignada', 0)
                                    ->where('status', true)
                                    ->where('is_executed', false)
                                    ->where('role_id', '!=', 1)
                                    ->where('solicituds.status', '=', Solicitud::STATUS_EN_PROCESO);
                            })->orWhere(function ($query) {
                                $query->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma')
                                    ->where('solicituds.is_reasignada', 1)
                                    ->where('is_reasignado', true)
                                    ->where('status', true)
                                    ->where('is_executed', false)
                                    ->where('role_id', '!=', 1)
                                    ->where('solicituds.status', '=', Solicitud::STATUS_EN_PROCESO);
                            });
                    });
            })->whereHas('reasignaciones', function ($q) use ($auth) {
                $q->where('user_subrogante_id', $auth->id);
            });
        })
            ->count();
    }

    private function totalRendicionesPendientes($auth)
    {
        $permissions_rendiciones    = [24, 25, 64];
        $status_proceso_rendicion   = [
            EstadoProcesoRendicionGasto::STATUS_APROBADO_N,
            EstadoProcesoRendicionGasto::STATUS_APROBADO_S,
            EstadoProcesoRendicionGasto::STATUS_ANULADO,
            EstadoProcesoRendicionGasto::STATUS_RECHAZADO
        ];
        $query = ProcesoRendicionGasto::where(function ($query) use ($auth, $permissions_rendiciones) {
            $query->whereHas('solicitud.firmantes', function ($q) use ($auth, $permissions_rendiciones) {
                $q->whereRaw('proceso_rendicion_gastos.posicion_firma_ok = solicitud_firmantes.posicion_firma')
                    ->where('role_id', '!=', 1)
                    ->where('user_id', $auth->id);
            })->orWhereHas('solicitud.firmantes', function ($q) use ($auth) {
                $q->whereIn('is_executed', [true, false])
                    ->whereRaw('proceso_rendicion_gastos.posicion_firma_ok = solicitud_firmantes.posicion_firma')
                    ->whereHas('funcionario.ausentismos', function ($q) use ($auth) {
                        $q->whereHas('subrogantes', function ($q) use ($auth) {
                            $q->where('users.id', $auth->id);
                        })->whereRaw("DATE(solicituds.fecha_by_user) >= ausentismos.fecha_inicio")
                            ->whereRaw("DATE(solicituds.fecha_by_user) <= ausentismos.fecha_termino");
                    });
            })->orWhere(function ($q) use ($auth) {
                $q->whereHas('solicitud.firmantes', function ($q) use ($auth) {
                    $q->whereIn('is_executed', [true, false])
                        ->whereRaw('proceso_rendicion_gastos.posicion_firma_ok = solicitud_firmantes.posicion_firma')
                        ->whereHas('funcionario.reasignacionAusencias', function ($q) use ($auth) {
                            $q->where('user_subrogante_id', $auth->id);
                        });
                })->whereHas('solicitud.reasignaciones', function ($q) use ($auth) {
                    $q->where('user_subrogante_id', $auth->id);
                });
            });
        });

        $query->where(function ($query) use ($status_proceso_rendicion) {
            $query->whereHas('solicitud', function ($q) use ($status_proceso_rendicion) {
                $q->where('status', Solicitud::STATUS_PROCESADO);
            })->whereNotIn('status', $status_proceso_rendicion);
        });

        return $query->count();
    }

    private function totalInformesPendientes($auth)
    {
        $query = Solicitud::where(function ($q) use ($auth) {
            $q->whereHas('firmantes', function ($query) use ($auth) {
                $query->where('status', true)
                    ->where('role_id', 3)
                    ->where('user_id', $auth->id);
            })->orWhereHas('firmantes', function ($query) use ($auth) {
                $query->where('role_id', 3)
                    ->whereHas('funcionario.ausentismos', function ($q) use ($auth) {
                        $q->whereHas('subrogantes', function ($q) use ($auth) {
                            $q->where('users.id', $auth->id);
                        })->whereRaw("DATE(solicituds.fecha_by_user) >= ausentismos.fecha_inicio")
                            ->whereRaw("DATE(solicituds.fecha_by_user) <= ausentismos.fecha_termino");
                    });
            })->orWhere(function ($q) use ($auth) {
                $q->whereHas('firmantes', function ($q) use ($auth) {
                    $q->where('role_id', 3)
                        ->whereHas('funcionario.reasignacionAusencias', function ($q) use ($auth) {
                            $q->where('user_subrogante_id', $auth->id);
                        });
                })->whereHas('reasignaciones', function ($q) use ($auth) {
                    $q->where('user_subrogante_id', $auth->id);
                });
            });
        });

        return
            $query->whereIn('status', [Solicitud::STATUS_EN_PROCESO, Solicitud::STATUS_PROCESADO])
            ->whereHas('informes', function ($q) {
                $q->whereIn('last_status', [EstadoInformeCometido::STATUS_INGRESADA, EstadoInformeCometido::STATUS_MODIFICADO]);
            })->count();
    }
}
