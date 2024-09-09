<?php

namespace App\Jobs;

use App\Exports\SolicitudesExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ExportSolicitudesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $solicitudes;
    protected $columns;
    protected $filePath;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($solicitudes, $columns, $filePath)
    {
        $this->solicitudes = $solicitudes;
        $this->columns = $columns;
        $this->filePath = $filePath;  // Ruta donde se almacenará el archivo temporalmente
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ini_set('memory_limit', '512M');
        Excel::queue(new SolicitudesExport($this->solicitudes, $this->columns), $this->filePath);
    }
}
