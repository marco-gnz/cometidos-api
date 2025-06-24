<?php

namespace App\Console\Commands;

use App\Models\Solicitud;
use App\Models\SoliucitudCalculo;
use Illuminate\Console\Command;

class UpdateMontoTotalPagarSolicitud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:monto-total-pagar-solicitud';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar monto total a pagar en solicitudes con cálculo de viático.';

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
        $this->info("Iniciando actualización de montos...");

        try {
            $total = 0;

            SoliucitudCalculo::chunk(500, function ($calculos) use (&$total) {
                foreach ($calculos as $calculo) {
                    try {
                        $total_valorizacion_value = $calculo->valorizacionTotalAjusteMonto()->total_valorizacion_value ?? 0;

                        if ($calculo->update(['monto_total_pagar' => $total_valorizacion_value])) {
                            $total++;
                        }
                    } catch (\Throwable $e) {
                        \Log::error("Error con cálculo ID {$calculo->id}: " . $e->getMessage());
                    }
                }
            });

            $this->info("$total registros actualizados correctamente.");
        } catch (\Exception $e) {
            \Log::error("Error general al ejecutar el comando: " . $e->getMessage());
            $this->error("Error al ejecutar el comando.");
        }
    }
}
