<?php

namespace App\Listeners;

use App\Events\SolicitudReasignada;
use App\Mail\SolicitudReasignada as MailSolicitudReasignada;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendSolicitudReasignadaNotification
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
     * @param  \App\Events\SolicitudReasignada  $event
     * @return void
     */
    public function handle(SolicitudReasignada $event)
    {
        if ($event->emails_copy) {
            Mail::to($event->last_status->funcionarioRs->email)
                ->cc($event->emails_copy)
                ->queue(
                    new MailSolicitudReasignada($event->solicitud, $event->last_status, $event->emails_copy)
                );
        } else {
            Mail::to($event->last_status->funcionarioRs->email)
                ->cc($event->last_status->funcionario->email)
                ->queue(
                    new MailSolicitudReasignada($event->solicitud, $event->last_status, $event->emails_copy)
                );
        }

    }
}
