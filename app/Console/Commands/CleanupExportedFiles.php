<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use File;

class CleanupExportedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exports:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Eliminar archivos exportados antiguos';

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
        $directory  = storage_path('app/exports');
        $files      = File::files($directory);

        foreach ($files as $file) {
            File::delete($file->getPathname());
            $this->info("Archivo eliminado: {$file->getFilename()}");
        }

        $this->info('Archivos antiguos eliminados.');
    }
}
