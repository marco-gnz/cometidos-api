<?php

namespace App\Listeners;

use App\Events\ProcesoRendicionGastoStatus;
use App\Mail\ProcesoRendicionGastoStatus as MailProcesoRendicionGastoStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendProcesoRendicionGastoStatusNotification
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
     * @param  \App\Events\ProcesoRendicionGastoStatus  $event
     * @return void
     */
    public function handle(ProcesoRendicionGastoStatus $event)
    {
        Mail::to($event->last_status->procesoRendicionGasto->userBy->email)
            ->queue(
                new MailProcesoRendicionGastoStatus($event->last_status)
            );
    }
}
