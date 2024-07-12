<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SolicitudReasignada extends Mailable
{
    use Queueable, SerializesModels;

    public $solicitud;
    public $last_status;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($solicitud, $last_status)
    {
        $this->solicitud = $solicitud;
        $this->last_status = $last_status;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = "GECOM - Solicitud de cometido reasignada";
        return $this->markdown('emails.solicitud.solicitud-reasignada')->subject($subject)->withSwiftMessage(function ($message) {
            $message->setPriority(\Swift_Message::PRIORITY_HIGHEST);
        });
    }
}
