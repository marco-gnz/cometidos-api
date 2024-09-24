<?php

namespace App\Jobs;

use App\Exports\SolicitudesExport;
use App\Models\Solicitud;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CreateSolicitudExportFile implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $folder;
    protected $columns;
    protected $solicitudes;
    protected $filter_all;

    public function __construct($folder, $columns, $solicitudes, $filter_all)
    {
        $this->folder = $folder;
        $this->columns = $columns;
        $this->solicitudes = $solicitudes;
        $this->filter_all = $filter_all;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Storage::disk('local')->makeDirectory($this->folder);
        Excel::store(new SolicitudesExport($this->solicitudes, $this->columns, $this->filter_all), "{$this->folder}/solicitudes.xlsx", 'local');
    }
}
