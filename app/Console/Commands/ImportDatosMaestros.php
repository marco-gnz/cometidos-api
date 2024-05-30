<?php

namespace App\Console\Commands;

use App\Models\Cargo;
use App\Models\Departamento;
use App\Models\SubDepartamento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportDatosMaestros extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:datosmaestros {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
            DB::beginTransaction();
            $file       = $this->argument('file');
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
            $imports = [];
            foreach ($data as $key => $row) {
                try {
                    $cargo                  = Cargo::where('nombre', $row['cargo'])->first();
                    if (!$cargo) {
                        $newCargo = Cargo::create([
                            'nombre'    => $row['cargo']
                        ]);

                        if ($newCargo) {
                            Log::info('Cargo creado!');
                        }
                    }
                    $departamento           = Departamento::where('nombre', $row['departamento'])->first();

                    if (!$departamento) {
                        $newDepto = Departamento::create([
                            'nombre'    => $row['departamento']
                        ]);

                        if ($newDepto) {
                            Log::info('Depto creado!');
                        }
                    }
                    $subdepartamento        = SubDepartamento::where('nombre', $row['subdepartamento'])->first();

                    if (!$subdepartamento) {
                        $newSubDepto = SubDepartamento::create([
                            'nombre'    => $row['subdepartamento']
                        ]);

                        if ($newSubDepto) {
                            Log::info('SubDepto creado!');
                        }
                    }
                } catch (\Exception $error) {
                    Log::info($error->getMessage());
                }
            }
            DB::commit();
        } catch (\Exception $error) {
            DB::rollback();
            Log::info($error->getMessage());
        }
    }
}
