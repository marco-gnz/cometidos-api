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
        } catch (\Exception $error) {
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
            $grupo_bd = Grupo::where('establecimiento_id', $establecimiento->id)
            ->where('departamento_id', $departamento->id)
            ->where('sub_departamento_id', $subdepartamento->id)
            ->first();

            if(!$grupo_bd){
                $grupo = Grupo::create([
                    'establecimiento_id'    => $establecimiento->id,
                    'departamento_id'       => $departamento->id,
                    'sub_departamento_id'   => $subdepartamento->id
                ]);

                if ($grupo) {
                    $grupo = $grupo->fresh();
                    $this->addFirmantesSearch($grupo, $row);
                }
            }

        } else {
            if(!$establecimiento){
                Log::info("no existe establecimiento: {$row['establecimiento']}");
            }

            if (!$departamento) {
                Log::info("no existe depto: {$row['departamento']}");
            }

            if (!$subdepartamento) {
                Log::info("no existe subdepto: {$row['subdepartamento']}");
            }
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
        // Verificar si grupo es un objeto y tiene la propiedad id
        if (!is_object($grupo) || !isset($grupo->id)) {
            Log::info("no existe grupo o el grupo no tiene ID");
            return;
        }

        for ($i = 1; $i <= 6; $i++) {
            try {
                $rut = $row["rut$i"] ?? null;
                $firmante = $row["firmante$i"] ?? null;

                if (!$rut || !$firmante) {
                    Log::info("Datos faltantes para firmante $i: rut o firmante");
                    continue;
                }

                $user = $this->findUser($rut);
                $role = $this->findRole($firmante);

                if (is_object($user) && isset($user->id) && is_object($role) && isset($role->id)) {
                    $new_firmante = [
                        'posicion_firma' => (int)$i,
                        'grupo_id' => $grupo->id,
                        'user_id' => $user->id,
                        'role_id' => $role->id
                    ];
                    Firmante::create($new_firmante);
                } else {
                    if (!is_object($user) || !isset($user->id)) {
                        Log::info("no existe usuario o el usuario no tiene ID: {$rut}");
                    }
                    if (!is_object($role) || !isset($role->id)) {
                        Log::info("no existe role o el role no tiene ID: {$firmante}");
                    }
                }
            } catch (\Exception $e) {
                Log::info("error en addFirmante: " . $e->getMessage());
                return;
            }
        }
    }




    private function findUser($rut)
    {
        $user = User::where('rut', $rut)->first();
        return $user;
    }

    private function findRole($name)
    {
        $role = Role::where('name', $name)->first();
        return $role;
    }
}
