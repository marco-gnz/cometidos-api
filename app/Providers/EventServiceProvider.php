<?php

namespace App\Providers;

use App\Events\ProcesoRendicionGastoCreated;
use App\Events\ProcesoRendicionGastoStatus;
use App\Events\SolicitudChangeStatus;
use App\Events\SolicitudCreated;
use App\Events\SolicitudReasignada;
use App\Events\SolicitudUpdated;
use App\Listeners\SendProcesoRendicionGastoCreatedNotification;
use App\Listeners\SendProcesoRendicionGastoStatusNotification;
use App\Listeners\SendSolicitudChangeStatusNotification;
use App\Listeners\SendSolicitudCreatedNotification;
use App\Listeners\SendSolicitudReasignadaNotification;
use App\Listeners\SendSolicitudUpdatedNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        SolicitudCreated::class => [
            SendSolicitudCreatedNotification::class
        ],
        SolicitudUpdated::class => [
            SendSolicitudUpdatedNotification::class
        ],
        SolicitudChangeStatus::class => [
            SendSolicitudChangeStatusNotification::class
        ],
        SolicitudReasignada::class => [
            SendSolicitudReasignadaNotification::class
        ],
        ProcesoRendicionGastoCreated::class => [
            SendProcesoRendicionGastoCreatedNotification::class
        ],
        ProcesoRendicionGastoStatus::class => [
            SendProcesoRendicionGastoStatusNotification::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
