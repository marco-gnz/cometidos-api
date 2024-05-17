<?php

namespace App\Listeners;

use App\Events\SolicitudUpdated;
use App\Mail\SolicitudUpdated as MailSolicitudUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendSolicitudUpdatedNotification
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
     * @param  \App\Events\SolicitudUpdated  $event
     * @return void
     */
    public function handle(SolicitudUpdated $event)
    {
        if ($event->emails_copy) {
            Mail::to($event->solicitud->funcionario->email)
                ->cc($event->emails_copy)
                ->queue(
                    new MailSolicitudUpdated($event->solicitud)
                );
        } else {
            Mail::to($event->solicitud->funcionario->email)
                ->queue(
                    new MailSolicitudUpdated($event->solicitud)
                );
        }
    }
}
