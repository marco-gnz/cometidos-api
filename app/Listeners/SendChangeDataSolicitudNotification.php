<?php

namespace App\Listeners;

use App\Events\ChangeDataSolicitud;
use App\Mail\ChangeDataSolicitud as MailChangeDataSolicitud;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendChangeDataSolicitudNotification
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
     * @param  \App\Events\ChangeDataSolicitud  $event
     * @return void
     */
    public function handle(ChangeDataSolicitud $event)
    {
        Mail::to($event->history->userSendBy->email)
            ->cc($event->history->userBy->email)
            ->queue(
                new MailChangeDataSolicitud($event->history)
            );
    }
}
