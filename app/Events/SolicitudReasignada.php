<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SolicitudReasignada
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $solicitud;
    public $last_status;
    public $emails_copy;

    public function __construct($solicitud, $last_status, $emails_copy)
    {
        $this->solicitud    = $solicitud;
        $this->last_status   = $last_status;
        $this->emails_copy   = $emails_copy;
    }
}
