<?php

namespace App\Console\Commands;

use App\Models\ProcesoRendicionGasto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdatePosicionFirmaProcesoRendicion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:posicion-firma-ok-rendicion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar posicion de firma ok de rendicion';

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
            $procesos = ProcesoRendicionGasto::orderBy('id', 'ASC')->get();

            foreach ($procesos as $proceso) {

                switch ($proceso->status) {
                    case 8:
                        $posicion_firma_ok = 0;
                        break;

                    case 0:
                    case 1:
                        $posicion_firma_ok = 1;
                        break;

                    case 2:
                    case 3:
                        $permissions_rendiciones    = [27];
                        $firma = $proceso->solicitud->firmantes()
                            ->where('posicion_firma', '>', $proceso->posicion_firma_actual)
                            ->where(function ($q) use ($permissions_rendiciones) {
                                foreach ($permissions_rendiciones as $permission_id) {
                                    return $q->whereJsonContains('permissions_id', $permission_id);
                                }
                            })
                            ->orderBy('posicion_firma', 'ASC')
                            ->first();

                        if ($firma) {
                            $posicion_firma_ok = $firma->posicion_firma;
                        }
                        break;

                    case 4:
                        $permissions_rendiciones    = [24];
                        $firma = $proceso->solicitud->firmantes()
                            ->where('posicion_firma', '>', $proceso->posicion_firma_actual)
                            ->where(function ($q) use ($permissions_rendiciones) {
                                foreach ($permissions_rendiciones as $permission_id) {
                                    return $q->whereJsonContains('permissions_id', $permission_id);
                                }
                            })
                            ->orderBy('posicion_firma', 'ASC')
                            ->first();

                        if ($firma) {
                            $posicion_firma_ok = $firma->posicion_firma;
                        }
                        break;

                    case 5:
                    case 6:
                        $permissions_rendiciones    = [24];
                        $firma = $proceso->solicitud->firmantes()
                            ->where(function ($q) use ($permissions_rendiciones) {
                                foreach ($permissions_rendiciones as $permission_id) {
                                    return $q->whereJsonContains('permissions_id', $permission_id);
                                }
                            })
                            ->orderBy('posicion_firma', 'DESC')
                            ->first();

                        if ($firma) {
                            $posicion_firma_ok = $firma->posicion_firma;
                        }
                        break;

                    case 7:
                        $posicion_firma_ok = 0;
                        break;
                }
                $update = $proceso->update(['posicion_firma_ok' => $posicion_firma_ok]);
                if ($update) {
                    $updatedCount++;
                }
            }

            $this->info("Actualización completada. Se actualizaron $updatedCount registros.");
        } catch (\Exception $error) {
            Log::error('Error al ejecutar el comando update:posicion-firma-rendicion: ' . $error->getMessage());
        }
        return 0;
    }
}
