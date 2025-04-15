<?php

namespace App\Console\Commands;

use App\Models\Solicitud;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateSolicitudNacionalidad extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:nacionalidad-solicitud';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar campo nacionalidad_id';

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
        try {
            Solicitud::whereHas('funcionario', function ($q) {
                $q->whereNotNull('nacionalidad_id');
            })
                ->whereNull('nacionalidad_id')
                ->chunk(500, function ($solicitudes) {
                    foreach ($solicitudes as $solicitud) {
                        $nacionalidad_id = optional($solicitud->funcionario->nacionalidad)->id;

                        if ($nacionalidad_id) {
                            $solicitud->update(['nacionalidad_id' => $nacionalidad_id]);
                        }
                    }
                });
        } catch (\Exception $error) {
            Log::error('Error al ejecutar el comando: ' . $error->getMessage());
        }
    }
}
