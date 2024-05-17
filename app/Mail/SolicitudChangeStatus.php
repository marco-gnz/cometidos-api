<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SolicitudChangeStatus extends Mailable
{
    use Queueable, SerializesModels;

    public $solicitud;
    public $last_status;
    public $emails_copy;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($solicitud, $last_status, $emails_copy)
    {
        $this->solicitud = $solicitud;
        $this->last_status = $last_status;
        $this->emails_copy = $emails_copy;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = env('APP_NAME') . " - " . "Actualización solicitud de cometido";
        return $this->markdown('emails.solicitud.solicitud-change-status')->subject($subject)->withSwiftMessage(function ($message) {
            $message->setPriority(\Swift_Message::PRIORITY_HIGHEST);
        });
    }
}
