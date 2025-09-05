<?php

namespace App\Console\Commands;

use App\Models\Convenio;
use App\Models\Estamento;
use App\Models\Ilustre;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Carbon\Carbon;

class ImportConvenios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:convenios {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar convenios de cometido';

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
            /* DB::beginTransaction(); */
            $file = $this->argument('file');
            $csvContent = file_get_contents($file);

            if ($csvContent === false) {
                $this->error("No se pudo leer el archivo: $file");
                return;
            }

            $lines = str_getcsv($csvContent, "\n");
            $headers = str_getcsv(array_shift($lines), ';');

            $data = [];

            foreach ($lines as $line) {
                // Verificar si la línea está vacía o contiene solo espacios en blanco
                if (empty(trim($line))) {
                    continue;
                }

                $row = str_getcsv($line, ';');
                $rowData = array_combine($headers, $row);
                $data[] = $rowData;
            }

            foreach ($data as $key => $row) {
                try {
                    $rut                        = $row['rut'];
                    $municipalidad              = $row['municipalidad'];
                    $planta                     = $row['planta'];
                    $tipo_contrato              = $row['tipo_contrato'];
                    $tipo_convenio              = $row['tipo_convenio'];
                    $fecha_desde                = $row['fecha_desde'];
                    $fecha_hasta                = $row['fecha_hasta'];
                    $n_viaticos_m               = $row['n_viaticos_m'];
                    $anio                       = $row['anio'];
                    $n_resolucion               = $row['n_resolucion'];
                    $fecha_resolucion           = $row['fecha_resolucion'];
                    $email                      = $row['email'];

                    $user               = User::where('rut', $rut)->first();
                    $muni               = Ilustre::where('nombre', $municipalidad)->first();
                    $estamento          = Estamento::where('nombre', $planta)->first();
                    if($user && $muni && $estamento){
                        $first_contrato = $user->contratos()->first();

                        $convenio = [
                            'fecha_inicio'          => Carbon::parse($fecha_desde)->format('Y-m-d'),
                            'fecha_termino'         => Carbon::parse($fecha_hasta)->format('Y-m-d'),
                            'fecha_resolucion'      => $fecha_resolucion ? Carbon::parse($fecha_resolucion)->format('Y-m-d') : NULL,
                            'n_resolucion'          => $n_resolucion ? $n_resolucion : NULL,
                            'n_viatico_mensual'     => $n_viaticos_m,
                            'tipo_convenio'         => Convenio::TYPE_COMETIDOS,
                            'tipo_contrato'         => $tipo_contrato,
                            'anio'                  => $anio,
                            'email'                 => $email !== NULL ? $email : NULL,
                            'active'                => $n_resolucion !== NULL ? true : false,
                            'user_id'               => $user->id,
                            'estamento_id'          => $estamento->id,
                            'ley_id'                => 4,
                            'establecimiento_id'    => 1,
                            'ilustre_id'            => $muni->id
                        ];
                        $new_convenio = Convenio::create($convenio);
                        if($new_convenio){
                            Log::info('convenio creado');
                        }
                    }else{
                        if (!$user) {
                            Log::info("no existe user: {$row['rut']}");
                        }

                        if (!$muni) {
                            Log::info("no existe muni: {$row['municipalidad']}");
                        }

                        if (!$estamento) {
                            Log::info("no existe estamento: {$row['estamento']}");
                        }
                    }



                } catch (\Exception $error) {
                    Log::info($error->getMessage());
                }
            }


        } catch (\Exception $error) {
            /* DB::rollback(); */
            Log::info($error->getMessage());
        }
    }
}
