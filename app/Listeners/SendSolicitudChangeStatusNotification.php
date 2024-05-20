<?php

namespace App\Listeners;

use App\Events\SolicitudChangeStatus;
use App\Mail\SolicitudChangeStatus as MailSolicitudChangeStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendSolicitudChangeStatusNotification
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
     * @param  \App\Events\SolicitudChangeStatus  $event
     * @return void
     */
    public function handle(SolicitudChangeStatus $event)
    {
        if($event->emails_copy){
            Mail::to($event->solicitud->funcionario->email)
            ->cc($event->emails_copy)
            ->queue(
                new MailSolicitudChangeStatus($event->solicitud, $event->last_status, null)
            );
        }else{
            Mail::to($event->solicitud->funcionario->email)
            ->queue(
                new MailSolicitudChangeStatus($event->solicitud, $event->last_status, null)
            );
        }

    }
}
