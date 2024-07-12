<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProcesoRendicionGastoCreated extends Mailable
{
    use Queueable, SerializesModels;
    public $proceso_rendicion;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($proceso_rendicion)
    {
        $this->proceso_rendicion = $proceso_rendicion;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = "GECOM - Nueva rendición de gasto";
        return $this->markdown('emails.procesorendiciongasto.procesorendiciongasto-created')->subject($subject)->withSwiftMessage(function ($message) {
            $message->setPriority(\Swift_Message::PRIORITY_HIGHEST);
        });
    }
}
