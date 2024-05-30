<?php

namespace App\Console\Commands;

use App\Models\Calidad;
use App\Models\Cargo;
use App\Models\Departamento;
use App\Models\Establecimiento;
use App\Models\Estamento;
use App\Models\Grado;
use App\Models\Hora;
use App\Models\Ley;
use App\Models\SubDepartamento;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:users {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar usuarios';

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
            $imports = [];

            foreach ($data as $key => $row) {
                try {
                    $rut                    = $row['rut'];
                    $dv                     = $row['dv'];
                    $nombres                = $row['nombres'];
                    $apellidos              = $row['apellidos'];
                    $ley                    = Ley::where('nombre', $row['ley'])->first();
                    $estamento              = Estamento::where('nombre', $row['estamento'])->first();
                    $grado                  = Grado::where('nombre', $row['grado'])->first();
                    $cargo                  = Cargo::where('nombre', $row['cargo'])->first();
                    $departamento           = Departamento::where('nombre', $row['departamento'])->first();
                    $subdepartamento        = SubDepartamento::where('nombre', $row['subdepartamento'])->first();
                    $establecimiento        = Establecimiento::where('cod_sirh', $row['establecimiento'])->first();
                    $hora                   = Hora::where('nombre', $row['hora'])->first();
                    $calidad                = Calidad::where('nombre', $row['calidad'])->first();

                    $user                   = User::where('rut', $rut)->where('dv', $dv)->first();

                    if ((!$user) && ($ley && $estamento && $grado && $cargo && $departamento && $subdepartamento && $establecimiento && $hora && $calidad)) {
                        $data = [
                            'rut'                   => $rut,
                            'dv'                    => $dv,
                            'nombres'               => $nombres,
                            'apellidos'             => $apellidos,
                            'email'                 => "{$rut}@redsalud.gob.cl",
                            'ley_id'                => $ley->id,
                            'estamento_id'          => $estamento->id,
                            'grado_id'              => $grado->id,
                            'cargo_id'              => $cargo->id,
                            'departamento_id'       => $departamento->id,
                            'sub_departamento_id'   => $subdepartamento->id,
                            'establecimiento_id'    => $establecimiento->id,
                            'hora_id'               => $hora->id,
                            'calidad_id'            => $calidad->id
                        ];

                        $user = User::create($data);

                        if ($user) {
                            $user->createToken('cometidos');
                            Log::info('Usuario creado!');
                        }
                    } else {
                        Log::info("Usuario de la celda {$key} no fue creado!");

                        if (!$ley) {
                            Log::info("{$row['ley']} no existe en ley. N° {$key}");
                        }

                        if (!$estamento) {
                            Log::info("{$row['estamento']} no existe en estamento. N° {$key}");
                        }

                        if (!$grado) {
                            Log::info("{$row['grado']} no existe en grado. N° {$key}");
                        }

                        if (!$cargo) {
                            Log::info("{$row['cargo']} no existe en cargo. N° {$key}");
                        }

                        if (!$departamento) {
                            Log::info("{$row['departamento']} no existe en departamento. N° {$key}");
                        }

                        if (!$subdepartamento) {
                            Log::info("{$row['subdepartamento']} no existe en subdepartamento. N° {$key}");
                        }

                        if (!$establecimiento) {
                            Log::info("{$row['establecimiento']} no existe en establecimiento. N° {$key}");
                        }

                        if (!$hora) {
                            Log::info("{$row['hora']} no existe en hora. N° {$key}");
                        }

                        if (!$calidad) {
                            Log::info("{$row['calidad']} no existe en calidad. N° {$key}");
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
