<?php

namespace App\Listeners;

use App\Events\ProcesoRendicionGastoCreated;
use App\Mail\ProcesoRendicionGastoCreated as MailProcesoRendicionGastoCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendProcesoRendicionGastoCreatedNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\ProcesoRendicionGastoCreated  $event
     * @return void
     */
    public function handle(ProcesoRendicionGastoCreated $event)
    {
        Mail::to($event->proceso_rendicion->userBy->email)
            ->queue(
                new MailProcesoRendicionGastoCreated($event->proceso_rendicion)
            );
    }
}
