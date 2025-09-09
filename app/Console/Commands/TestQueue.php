<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\TestJob;

class TestQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba de queues';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Envía el trabajo a la cola
        dispatch(new TestJob());

        $this->info('Trabajo de prueba enviado a la cola.');
    }
}
