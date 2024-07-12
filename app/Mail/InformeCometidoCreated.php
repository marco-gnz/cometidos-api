<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InformeCometidoCreated extends Mailable
{
    use Queueable, SerializesModels;
    public $informe_cometido;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($informe_cometido)
    {
        $this->informe_cometido = $informe_cometido;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = "GECOM - Nuevo informe de cometido";
        return $this->markdown('emails.informecometido.informecometido-created')->subject($subject)->withSwiftMessage(function ($message) {
            $message->setPriority(\Swift_Message::PRIORITY_HIGHEST);
        });
    }
}
