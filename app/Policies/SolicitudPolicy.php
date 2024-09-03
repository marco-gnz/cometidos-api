<?php

namespace App\Policies;

use App\Models\Configuration;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Traits\FirmaDisponibleTrait;

class SolicitudPolicy
{
    use HandlesAuthorization, FirmaDisponibleTrait;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        //
    }

    public function verdatos(User $user, Solicitud $solicitud)
    {
        $firma = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.datos.ver');

        if ($user->estado && $user->hasRole('SUPER ADMINISTRADOR') || $firma->is_firma) {
            return true;
        }

        return false;
    }

    public function verfirmantes(User $user, Solicitud $solicitud)
    {
        $firma = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.firmantes.ver');

        if ($user->estado && $user->hasRole('SUPER ADMINISTRADOR') || $firma->is_firma) {
            return true;
        }

        return false;
    }

    public function vervalorizacion(User $user, Solicitud $solicitud)
    {
        $firma = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.valorizacion.ver');

        if ($user->estado && $user->hasRole('SUPER ADMINISTRADOR') || $firma->is_firma) {
            return true;
        }

        return false;
    }

    public function verconvenio(User $user, Solicitud $solicitud)
    {
        $firma = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.convenio.ver');

        if ($user->estado && $user->hasRole('SUPER ADMINISTRADOR') || $firma->is_firma) {
            return true;
        }

        return false;
    }

    public function verrendicion(User $user, Solicitud $solicitud)
    {
        $firma = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.rendiciones.ver');

        if ($user->estado && $user->hasRole('SUPER ADMINISTRADOR') || $firma->is_firma) {
            return true;
        }

        return false;
    }

    public function verarchivos(User $user, Solicitud $solicitud)
    {
        $firma = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.archivos.ver');

        if ($user->estado && $user->hasRole('SUPER ADMINISTRADOR') || $firma->is_firma) {
            return true;
        }

        return false;
    }

    public function verinformes(User $user, Solicitud $solicitud)
    {
        $firma = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.informes.ver');

        if ($user->estado && $user->hasRole('SUPER ADMINISTRADOR') || $firma->is_firma) {
            return true;
        }

        return false;
    }

    public function verhistorial(User $user, Solicitud $solicitud)
    {
        $firma = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.historial.ver');

        if ($user->estado && $user->hasRole('SUPER ADMINISTRADOR') || $firma->is_firma) {
            return true;
        }

        return false;
    }
    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Solicitud  $solicitud
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Solicitud $solicitud)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        $now = Carbon::now()->format('Y-m-d');
        $last_contrato = $user->lastContrato();
        $establecimiento_id = $last_contrato ? $last_contrato->establecimiento_id : 1;
        $total_informes_pendientes = (int)Configuration::obtenerValor('informecometido.total_pendiente', $establecimiento_id);
        $total_por_ingresar_informes = $user->solicitudes()
            ->where('status', '!=', Solicitud::STATUS_ANULADO)
            ->where('fecha_termino', '<', $now)
            ->whereDoesntHave('informes')
            ->count();
        $is_solicitud = $user->is_solicitud ? true : false;
        if ($total_por_ingresar_informes > $total_informes_pendientes || !$user->is_solicitud) {
            return false;
        }
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Solicitud  $solicitud
     * @return \Illuminate\Auth\Access\Response|bool
     */

    public function update(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO) {
            return false;
        }
        $status_disponibles = [
            EstadoSolicitud::STATUS_INGRESADA,
            EstadoSolicitud::STATUS_MODIFICADA,
            EstadoSolicitud::STATUS_RECHAZADO,
            EstadoSolicitud::STATUS_PENDIENTE
        ];
        if ((in_array($solicitud->last_status, $status_disponibles)) && $solicitud->posicion_firma_actual === 0 && $solicitud->user_id === $user->id) {
            return true;
        }
        return false;
    }

    public function updateadmin(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO) {
            return false;
        }
        $status_disponibles = [
            EstadoSolicitud::STATUS_INGRESADA,
            EstadoSolicitud::STATUS_MODIFICADA,
            EstadoSolicitud::STATUS_RECHAZADO,
            EstadoSolicitud::STATUS_PENDIENTE
        ];
        $firma          = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.datos.editar-solicitud');

        if($firma->is_firma && in_array($solicitud->last_status, $status_disponibles)){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Solicitud  $solicitud
     * @return \Illuminate\Auth\Access\Response|bool
     */

    public function firma(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO) {
            return false;
        }

        $firma = $this->obtenerFirmaDisponible($solicitud, 'solicitud.firma.validar');

        if ($firma->is_firma && $solicitud->status === Solicitud::STATUS_EN_PROCESO) {
            return true;
        }

        return false;
    }

    public function reasignaremergency(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO || $solicitud->status === Solicitud::STATUS_EN_PROCESO) {
            return false;
        }
        if ($user->estado && $user->hasRole('SUPER ADMINISTRADOR')) {
            return true;
        }

        return false;
    }

    public function anular(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO || $solicitud->status === Solicitud::STATUS_PROCESADO) {
            return false;
        }

        if ($solicitud->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function anularAdmin(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO) {
            return false;
        }
        $firma = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.firma.anular');

        if ($firma->is_firma) {
            return true;
        }

        return false;
    }

    public function sincronizargrupo(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO) {
            return false;
        }

        $status_disponibles = [
            EstadoSolicitud::STATUS_INGRESADA,
            EstadoSolicitud::STATUS_MODIFICADA,
            EstadoSolicitud::STATUS_PENDIENTE,
            EstadoSolicitud::STATUS_RECHAZADO
        ];
        $last_status    = $solicitud->estados()->orderBy('id', 'DESC')->first();
        $firma          = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.datos.sincronizar-grupo');
        if((in_array($solicitud->last_status, $status_disponibles) || ($last_status && ($last_status->s_role_id === 3 || $last_status->r_s_role_id === 3))) && ($firma->is_firma || $user->hasPermissionTo('solicitud.datos.sincronizar-grupo'))){
            return true;
        }

        return false;
    }

    public function createcalculo(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO || $solicitud->status === Solicitud::STATUS_PROCESADO) {
            return false;
        }
        $firma  = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.valorizacion.crear');
        if ($firma->is_firma) {
            return true;
        }
        return false;
    }

    public function createcalculoajuste(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO) {
            return false;
        }
        $firma  = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.ajuste.crear');
        if ($firma->is_firma && $solicitud->getLastCalculo()) {
            return true;
        }
        return false;
    }

    public function deletecalculoajuste(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO || $solicitud->status === Solicitud::STATUS_PROCESADO) {
            return false;
        }
        $firma  = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.ajuste.eliminar');
        if ($firma->is_firma && $solicitud->getLastCalculo()) {
            return true;
        }
        return false;
    }

    public function createconvenio(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_ANULADO) {
            return false;
        }
        $firma  = $this->isFirmaDisponibleActionPolicy($solicitud, 'solicitud.convenio.crear');
        if ($firma->is_firma) {
            return true;
        }
        return false;
    }

    public function loadsirh(User $user, Solicitud $solicitud)
    {
        if ($solicitud->status === Solicitud::STATUS_PROCESADO && $user->hasPermissionTo('solicitud.datos.load-sirh')) {
            return true;
        }

        return false;
    }

    public function export(User $user)
    {
        if ($user->hasPermissionTo('reporte.solicitud')) {
            return true;
        }
        return false;
    }

    public function delete(User $user, Solicitud $solicitud)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Solicitud  $solicitud
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Solicitud $solicitud)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Solicitud  $solicitud
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Solicitud $solicitud)
    {
        //
    }
}
