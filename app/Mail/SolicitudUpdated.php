<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SolicitudUpdated extends Mailable
{
    use Queueable, SerializesModels;
    public $solicitud;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($solicitud)
    {
        $this->solicitud = $solicitud;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = env('APP_NAME') . " - " . "Solicitud de cometido modificada";
        return $this->markdown('emails.solicitud.solicitud-updated')->subject($subject)->withSwiftMessage(function ($message) {
            $message->setPriority(\Swift_Message::PRIORITY_HIGHEST);
        });
    }
}
