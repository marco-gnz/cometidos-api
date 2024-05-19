<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InformeCometidoStatus
{
    use Dispatchable, SerializesModels;

    public $last_status;

    public function __construct($last_status)
    {
        $this->last_status    = $last_status;
    }
}
