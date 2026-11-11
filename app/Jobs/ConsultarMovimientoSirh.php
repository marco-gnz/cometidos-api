<?php

namespace App\Jobs;

use App\Services\Sirh\ConsultaPersistidaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ConsultarMovimientoSirh implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 0;
    public $backoff = 30;
    private $expiresAt;
    private $solicitudId;
    private $clave;
    private $token;

    public function __construct($solicitudId, $clave, $token)
    {
        $this->expiresAt = time() + 600;
        $this->solicitudId = $solicitudId; $this->clave = $clave; $this->token = $token;
    }

    public function retryUntil() { return \Carbon\Carbon::createFromTimestamp($this->expiresAt); }

    public function handle(ConsultaPersistidaService $service)
    {
        if (!$service->ejecutar($this->solicitudId, $this->clave, $this->token)) $this->release(5);
    }
}
