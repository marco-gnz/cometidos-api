<?php

namespace App\Console\Commands;

use App\Models\InformeCometido;
use Illuminate\Console\Command;

class UpdateInformeStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:informe-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el estado de ingreso de todos los registros del modelo Informe';

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
        $informes = InformeCometido::all();
        $updatedCount = 0;

        foreach ($informes as $informe) {
            $currentStatus = $informe->status_ingreso;
            $newStatus = $informe->diffPlazoTardioInforme($informe);

            if ($currentStatus !== $newStatus) {
                $informe->status_ingreso = $newStatus;
                $informe->save();
                $updatedCount++;
            }
        }

        $this->info("Actualización completada. Se actualizaron $updatedCount registros.");
    }
}
