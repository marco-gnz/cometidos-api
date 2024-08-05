<?php

namespace App\Console\Commands;

use App\Models\EstadoInformeCometido;
use App\Models\EstadoSolicitud;
use App\Models\InformeCometido;
use App\Models\Solicitud;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AnularInformeCometidosAnulados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:informe-anular';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Anular informes de cometidos anulados';

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
            $informes = InformeCometido::whereNotIn('last_status', [EstadoInformeCometido::STATUS_ANULADO, EstadoInformeCometido::STATUS_RECHAZADO])
            ->whereHas('solicitud', function ($q) {
                $q->where('status', Solicitud::STATUS_ANULADO);
            })
                ->get();
            foreach ($informes as $informe) {
                $informe->update([
                    'last_status'   => EstadoInformeCometido::STATUS_ANULADO
                ]);
            }
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }
}
