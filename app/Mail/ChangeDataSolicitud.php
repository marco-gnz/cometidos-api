<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChangeDataSolicitud extends Mailable
{
    use Queueable, SerializesModels;
    public $history;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($history)
    {
        $this->history = $history;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = env('APP_NAME') . " - " . "Solicitud de modificación de datos";
        return $this->markdown('emails.changedata.change-data')->subject($subject)->withSwiftMessage(function ($message) {
            $message->setPriority(\Swift_Message::PRIORITY_HIGHEST);
        });
    }
}
