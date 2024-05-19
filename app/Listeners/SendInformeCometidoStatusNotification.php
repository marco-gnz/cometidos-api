<?php

namespace App\Listeners;

use App\Events\InformeCometidoStatus;
use App\Mail\InformeCometidoStatus as MailInformeCometidoStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendInformeCometidoStatusNotification
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
     * @param  \App\Events\InformeCometidoStatus  $event
     * @return void
     */
    public function handle(InformeCometidoStatus $event)
    {
        Mail::to($event->last_status->InformeCometido->userBy->email)
            ->queue(
                new MailInformeCometidoStatus($event->last_status)
            );
    }
}
