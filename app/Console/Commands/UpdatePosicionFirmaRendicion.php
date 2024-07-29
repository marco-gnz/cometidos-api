<?php

namespace App\Console\Commands;

use App\Models\ProcesoRendicionGasto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdatePosicionFirmaRendicion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:posicion-firma-rendicion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar posición de firma en rendiciones';

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
        $updatedCount = 0;
        try {
            $procesos = ProcesoRendicionGasto::whereNotIn('status', [0, 1, 7])->get();

            foreach ($procesos as $proceso) {
                $last_status = $proceso->estados()->orderBy('id', 'DESC')->first();
                $proceso->update(['posicion_firma_actual' => $last_status->posicion_firma]);
                $updatedCount++;
            }

            $this->info("Actualización completada. Se actualizaron $updatedCount registros.");
        } catch (\Exception $error) {
            Log::error('Error al ejecutar el comando update:posicion-firma-rendicion: ' . $error->getMessage());
        }
        return 0;
    }
}
