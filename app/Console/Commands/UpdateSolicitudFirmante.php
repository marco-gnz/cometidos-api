<?php

namespace App\Console\Commands;

use App\Models\Solicitud;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateSolicitudFirmante extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:solicitud-firmante';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar solicitud con último firmante y fecha';

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
            Solicitud::chunk(100, function ($solicitudes) {
                foreach ($solicitudes as $solicitud) {
                    $last_status = $solicitud->estados()->orderBy('id', 'DESC')->first();
                    if ($last_status) {
                        $posicion_firma = $last_status->firmaS->posicion_firma;
                        $siguiente_firmante = $solicitud->firmantes()->where('status', true)->where('posicion_firma', '>', $posicion_firma)->orderBy('posicion_firma', 'ASC')->first();
                        if ($siguiente_firmante) {
                            $posicion_firma_ok = $siguiente_firmante->posicion_firma;
                        } else {
                            $posicion_firma_ok = $last_status->posicion_firma;
                        }

                        if ($last_status->is_reasignado) {
                            $posicion_firma_ok = $last_status->firmaRs->posicion_firma;
                        }
                        $solicitud->update([
                            'posicion_firma_ok'     => $posicion_firma_ok,
                            'fecha_last_firma'      => $last_status->created_at
                        ]);
                    }
                }
            });
        } catch (\Exception $error) {
            Log::error('Error al ejecutar el comando: ' . $error->getMessage());
        }
    }
}
