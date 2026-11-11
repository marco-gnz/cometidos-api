<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class ProcesarColaSirh extends Command
{
    protected $signature = 'sirh:procesar-cola';
    protected $description = 'Procesa la cola SIRH por lotes desde el scheduler existente';

    public function handle()
    {
        $limite = microtime(true) + 50;
        $cola = Queue::connection('sirh_database');

        do {
            if ($cola->size('sirh') === 0) break;
            $status = $this->call('queue:work', [
                'connection' => 'sirh_database',
                '--queue' => 'sirh',
                '--once' => true,
                '--sleep' => 1,
                '--timeout' => 60,
            ]);
            if ($status !== 0) return $status;
        } while (microtime(true) < $limite);

        return 0;
    }
}
