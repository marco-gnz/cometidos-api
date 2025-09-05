<?php

namespace App\Console\Commands;

use App\Models\Solicitud;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportLoadSirh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:loads {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check para cometidos cargados en SIRH';

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
                    $loads = [];
                    $n_resolucion = $row['n_resolucion'];
                    $solicitud = Solicitud::where('codigo', $n_resolucion)->first();

                    if($solicitud){
                        $loads[] = [
                            'load_sirh'     => true
                        ];
                        $create_loads = $solicitud->addLoads($loads);
                        $loads = [];
                    }
                } catch (\Exception $error) {
                    Log::info($error->getMessage());
                }
            }

        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }
}
