<?php

namespace App\Listeners;

use App\Events\SolicitudCreated;
use App\Mail\SolicitudCreated as MailSolicitudCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendSolicitudCreatedNotification
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
     * @param  \App\Events\SolicitudCreated  $event
     * @return void
     */
    public function handle(SolicitudCreated $event)
    {
        if ($event->emails_copy) {
            Mail::to($event->solicitud->funcionario->email)
                ->cc($event->emails_copy)
                ->queue(
                    new MailSolicitudCreated($event->solicitud)
                );
        } else {
            Mail::to($event->solicitud->funcionario->email)
                ->queue(
                    new MailSolicitudCreated($event->solicitud)
                );
        }
    }
}
