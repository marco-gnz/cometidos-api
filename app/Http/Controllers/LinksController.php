<?php

namespace App\Http\Controllers;

use App\Models\Ausentismo;
use Illuminate\Http\Request;
use App\Models\Link;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LinksController extends Controller
{
    public function getLinks()
    {
        $user = Auth::user();

        $is_show_solicitud = false;
        $is_show_rendicion = false;
        $firmas = $user->firmas()->where('status', true)->where('role_id', '!=', 1)->count();
        $reasignaciones = $user->reasignacionAsignadas()->count();
        $subrogancias = Ausentismo::whereHas('subrogantes', function ($q) use($user) {
            $q->where('users.id', $user->id);
        })->count();
        if ($firmas > 0 || $subrogancias > 0 || $reasignaciones > 0 || $user->hasRole('SUPER ADMINISTRADOR') || $user->hasPermissionTo('solicitudes.ver')) {
            $is_show_solicitud = true;
        }

        if ($firmas > 0 || $subrogancias > 0 || $reasignaciones > 0 || $user->hasRole('SUPER ADMINISTRADOR') || $user->hasPermissionTo('rendiciones.ver')) {
            $is_show_rendicion = true;
        }

        $linksUsers = [];
        $linksAdmin = [];

        if($is_show_solicitud){
            $linksAdmin[] = Link::create(
                "list-solicitudes",
                "Solicitudes de cometido",
                "Listado de solicitudes de cometido",
                "/firmante/solicitudes",
                "0e6db8",
                false
            );
        }

        if($is_show_rendicion){
            $linksAdmin[] = Link::create(
                "list-rendicion-gastos",
                "Rendiciones de gastos",
                "Listado de rendiciones de gastos",
                "/firmante/rendiciones",
                "0e6db8",
                false
            );
        }

        $linksUsers = [];
        if ($user->hasPermissionTo('grupofirma.ver')) {
            $linksUsers[] = Link::create(
                "list-grupos",
                "Grupos de firmantes",
                "Listado de grupos de firmantes",
                "/admin/grupos",
                "0e6db8",
                false
            );
        }
        if ($user->hasPermissionTo('convenio.ver')) {
            $linksUsers[] = Link::create(
                "list-convenios",
                "Convenios",
                "Convenios de cometido",
                "/admin/convenios",
                "0e6db8",
                false
            );
        }
        if ($user->hasPermissionTo('ausentismo.ver')) {
            $linksUsers[] = Link::create(
                "list-ausentismos",
                "Ausentismos",
                "Listado de ausentismos",
                "/admin/ausentismos",
                "0e6db8",
                false
            );
        }
        if ($user->hasPermissionTo('reasignacion.ver')) {
            $linksUsers[] = Link::create(
                "list-reasignaciones",
                "Reasignaciones",
                "Listado de reasignaciones",
                "/admin/reasignaciones",
                "0e6db8",
                false
            );
        }
        if ($user->hasPermissionTo('funcionario.ver')) {
            $linksUsers[] = Link::create(
                "list-funcionarios",
                "Funcionarios",
                "Listado de funcionarios",
                "/admin/funcionarios",
                "0e6db8",
                false
            );
        }
        if ($user->hasPermissionTo('usuarioespecial.ver')) {
            $linksUsers[] = Link::create(
                "list-usuarios-especiales",
                "Usuarios especiales",
                "Usuarios especiales",
                "/admin/usuarios-especiales",
                "0e6db8",
                false
            );
        }
        if ($user->hasPermissionTo('configuracion.ver')) {
            $linksUsers[] = Link::create(
                "list-configuraciones",
                "Configuraciones",
                "Listado de configuraciones",
                "/admin/configuraciones",
                "0e6db8",
                false
            );
        }
        if ($user->hasPermissionTo('perfil.ver')) {
            $linksUsers[] = Link::create(
                "list-admin",
                "Perfiles",
                "Listado de usuarios con perfiles",
                "/admin/perfiles",
                "0e6db8",
                false
            );
        }

        return response()->json([
            'links_admin' => $linksAdmin,
            'links_users' => $linksUsers,
        ]);
    }
}
