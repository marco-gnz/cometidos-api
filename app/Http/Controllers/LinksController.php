<?php

namespace App\Http\Controllers;

use App\Models\Ausentismo;
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

        $firmas = Solicitud::where('status', Solicitud::STATUS_EN_PROCESO)
            ->where(function ($q) use ($user) {
                $q->whereHas('firmantes', function ($q) use ($user) {
                    $q->where(function ($q) use ($user) {
                        $q->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma - 1')
                            ->where('solicituds.is_reasignada', 0)
                            ->where('status', true)
                            ->where('is_executed', false)
                            ->where('role_id', '!=', 1)
                            ->where('user_id', $user->id);
                    })
                        ->orWhere(function ($q) use ($user) {
                            $q->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma')
                                ->where('solicituds.is_reasignada', 1)
                                ->where('is_reasignado', true)
                                ->where('status', true)
                                ->where('is_executed', false)
                                ->where('role_id', '!=', 1)
                                ->where('user_id', $user->id);
                        });
                });
            })->orWhere(function ($q) use ($user) {
                $q->whereHas('firmantes', function ($q) use ($user) {
                    $q->whereHas('funcionario.ausentismos', function ($q) use ($user) {
                        $q->whereHas('subrogantes', function ($q) use ($user) {
                            $q->where('users.id', $user->id);
                        })
                            ->whereRaw("DATE(solicituds.fecha_by_user) >= ausentismos.fecha_inicio")
                            ->whereRaw("DATE(solicituds.fecha_by_user) <= ausentismos.fecha_termino")
                            ->where(function ($query) {
                                $query->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma - 1')
                                    ->where('solicituds.is_reasignada', 0)
                                    ->where('status', true)
                                    ->where('is_executed', false)
                                    ->where('role_id', '!=', 1);
                            })->orWhere(function ($query) {
                                $query->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma')
                                    ->where('solicituds.is_reasignada', 1)
                                    ->where('is_reasignado', true)
                                    ->where('status', true)
                                    ->where('is_executed', false)
                                    ->where('role_id', '!=', 1);
                            });
                    });
                });
            })->orWhere(function ($q) use ($user) {
                $q->whereHas('firmantes', function ($q) use ($user) {
                    $q->where('is_executed', false)
                        ->whereHas('funcionario.reasignacionAusencias', function ($q) use ($user) {
                            $q->where('user_subrogante_id', $user->id)
                                ->where(function ($query) {
                                    $query->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma - 1')
                                        ->where('solicituds.is_reasignada', 0)
                                        ->where('status', true)
                                        ->where('is_executed', false)
                                        ->where('role_id', '!=', 1);
                                })->orWhere(function ($query) {
                                    $query->whereRaw('solicituds.posicion_firma_actual = solicitud_firmantes.posicion_firma')
                                        ->where('solicituds.is_reasignada', 1)
                                        ->where('is_reasignado', true)
                                        ->where('status', true)
                                        ->where('is_executed', false)
                                        ->where('role_id', '!=', 1);
                                });
                        });
                })->whereHas('reasignaciones', function ($q) use ($user) {
                    $q->where('user_subrogante_id', $user->id);
                });
            })
            ->count();

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
                "Firma de cometidos e informes",
                "/firmante/solicitudes",
                "d93c47",
                false,
                $firmas
            );
        }

        if ($is_show_rendicion) {
            $linksAdmin[] = Link::create(
                "list-rendicion-gastos",
                "Rendiciones pendientes de firma",
                "Firma de rendición de gastos",
                "/firmante/rendiciones",
                "0e6db8",
                false,
                null
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

        return response()->json([
            'links_admin' => $linksAdmin,
            'links_users' => $linksUsers,
        ]);
    }
}
