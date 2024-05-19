<?php

namespace App\Listeners;

use App\Events\InformeCometidoCreated;
use App\Mail\InformeCometidoCreated as MailInformeCometidoCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendInformeCometidoCreatedNotification
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
     * @param  \App\Events\InformeCometidoCreated  $event
     * @return void
     */
    public function handle(InformeCometidoCreated $event)
    {
        Mail::to($event->informe_cometido->userBy->email)
            ->queue(
                new MailInformeCometidoCreated($event->informe_cometido)
            );
    }
}
