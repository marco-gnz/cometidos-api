<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InformeCometidoStatus extends Mailable
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
        $subject = env('APP_NAME') . " - " . "Actualización informe de cometido";
        return $this->markdown('emails.informecometido.informecometido-status')->subject($subject)->withSwiftMessage(function ($message) {
            $message->setPriority(\Swift_Message::PRIORITY_HIGHEST);
        });
    }
}
