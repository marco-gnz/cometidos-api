<?php

namespace App\Policies;

use App\Models\Solicitud;
use App\Models\SolicitudFirmante;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Role;

class SolicitudFirmantePolicy
{
    use HandlesAuthorization;

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

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SolicitudFirmante  $solicitudFirmante
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, SolicitudFirmante $solicitudFirmante)
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
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SolicitudFirmante  $solicitudFirmante
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, SolicitudFirmante $solicitudFirmante)
    {
        if ((!$user->hasRole('SUPER ADMINISTRADOR')) &&  $solicitudFirmante->solicitud->status === Solicitud::STATUS_ANULADO || $solicitudFirmante->posicion_firma === 0) {
            return false;
        }

        if ((!$user->hasRole('SUPER ADMINISTRADOR')) && $solicitudFirmante->posicion_firma > $solicitudFirmante->solicitud->posicion_firma_actual) {
            if ($this->isFinanzas($solicitudFirmante) && !$solicitudFirmante->solicitud->derecho_pago) {
                return false;
            }
            return true;
        }

        return false;
    }

    private function isFinanzas($solicitudFirmante)
    {
        $rolesFinanzas = Role::whereIn('name', ['REVISOR FINANZAS', 'JEFE FINANZAS'])->pluck('id')->toArray();
        return in_array($solicitudFirmante->role_id, $rolesFinanzas);
    }


    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SolicitudFirmante  $solicitudFirmante
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, SolicitudFirmante $solicitudFirmante)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SolicitudFirmante  $solicitudFirmante
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, SolicitudFirmante $solicitudFirmante)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SolicitudFirmante  $solicitudFirmante
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, SolicitudFirmante $solicitudFirmante)
    {
        //
    }
}
