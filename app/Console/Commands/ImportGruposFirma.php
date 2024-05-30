<?php

namespace App\Console\Commands;

use App\Models\Departamento;
use App\Models\Establecimiento;
use App\Models\Firmante;
use App\Models\Grupo;
use App\Models\SubDepartamento;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class ImportGruposFirma extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:gruposfirma {file}';

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
        /* DB::beginTransaction(); */

        try {
            $file = $this->argument('file');
            $csvContent = file_get_contents($file);

            if ($csvContent === false) {
                $this->error("No se pudo leer el archivo: $file");
                return;
            }

            $lines = str_getcsv($csvContent, "\n");
            $headers = str_getcsv(array_shift($lines), ';');

            foreach ($lines as $key => $line) {
                // Verificar si la línea está vacía o contiene solo espacios en blanco
                if (empty(trim($line))) {
                    continue;
                }

                $row = str_getcsv($line, ';');
                $rowData = array_combine($headers, $row);
                $this->processRow($key, $rowData);
            }

            /*  DB::commit(); */
        } catch (\Exception $error) {
            /*  DB::rollback(); */
            Log::info("Error: " . $error->getMessage());
        }
    }

    private function processRow($key, $row)
    {
        $establecimiento = $this->findEstablecimiento($row['establecimiento']);
        $departamento = $this->findDepartamento($row['departamento']);
        $subdepartamento = $this->findSubdepartamento($row['subdepartamento']);
        // Buscar y asignar otros datos necesarios...

        if ($establecimiento && $departamento && $subdepartamento) {
            $grupo = Grupo::create([
                'establecimiento_id'    => $establecimiento->id,
                'departamento_id'       => $departamento->id,
                'sub_departamento_id'   => $subdepartamento->id
            ]);

            if ($grupo) {
                $this->addFirmantesSearch($grupo, $row);
            }
        } else {
            Log::info("$key - no existe data!");
        }
    }

    private function findEstablecimiento($sigla)
    {
        return Establecimiento::where('sigla', $sigla)->first();
    }

    private function findDepartamento($departamento)
    {
        return Departamento::where('nombre', $departamento)->first();
    }

    private function findSubdepartamento($subdepartamento)
    {
        return SubDepartamento::where('nombre', $subdepartamento)->first();
    }

    // Implementar métodos similares para encontrar otros datos necesarios...

    private function addFirmantesSearch($grupo, $row)
    {
        for ($i = 1; $i <= 6; $i++) {
            try {
                $user       = $this->findUser($row["rut$i"]);
                $firmante   = $this->findRole($row["firmante$i"]);

                if ($user && $firmante && $grupo) {
                    $new_firmante = [
                        'posicion_firma'    => (int)$i,
                        'grupo_id'          => $grupo->id,
                        'user_id'           => $user->id,
                        'role_id'           => $firmante->id
                    ];
                    Firmante::create($new_firmante);
                }
            } catch (\Exception $e) {
                Log::info($e->getMessage());
                return;
            }
        }
    }


    private function findUser($rut)
    {
        $user = User::where('rut', $rut)->first();

        if (!$user) {
            throw new \Exception("User not found for rut: $rut");
        }

        return $user;
    }

    private function findRole($name)
    {
        $role = Role::where('name', $name)->first();

        if (!$role) {
            throw new \Exception("Role not found for name: $name");
        }

        return $role;
    }
}
