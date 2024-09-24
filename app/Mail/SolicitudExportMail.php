<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SolicitudExportMail extends Mailable
{
    use Queueable, SerializesModels;
    public $filePath;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('GECOM - Exportación de Solicitudes')
        ->view('emails.solicitud-export')
        ->attach($this->filePath, [
            'as'    => 'solicitudes.xlsx',
            'mime'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
