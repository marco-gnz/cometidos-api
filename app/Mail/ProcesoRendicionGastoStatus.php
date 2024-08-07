<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProcesoRendicionGastoStatus extends Mailable
{
    use Queueable, SerializesModels;

    public $last_status;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($last_status)
    {
        $this->last_status = $last_status;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = "GECOM - Actualización rendición de gasto";
        return $this->markdown('emails.procesorendiciongasto.procesorendiciongasto-status')->subject($subject)->withSwiftMessage(function ($message) {
            $message->setPriority(\Swift_Message::PRIORITY_HIGHEST);
        });
    }
}
