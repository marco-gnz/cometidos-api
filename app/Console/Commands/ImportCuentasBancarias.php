<?php

namespace App\Console\Commands;

use App\Models\Banco;
use App\Models\CuentaBancaria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class ImportCuentasBancarias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:cuentasbancarias {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar cuentas bancarias';

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
            $cuentas = [];

            foreach ($data as $key => $row) {
                try {
                    $rut                    = $row['rut'];
                    $n_cuenta               = $row['n_cuenta'];
                    $tipo_cuenta            = $row['tipo_cuenta'];
                    $banco                  = $row['banco'];

                    $banco      = Banco::where('nombre', $banco)->first();
                    $user       = User::where('rut_completo', $rut)->first();

                    if ($user) {
                        switch ($tipo_cuenta) {
                            case 'Cash':
                                $tipo_cuenta_ok = CuentaBancaria::TYPE_ACCOUNT_6;
                                $cuentabancaria = [
                                    'n_cuenta'      => NULL,
                                    'banco_id'      => NULL,
                                ];
                                break;

                            case 'Chequera Electrónica':
                                $tipo_cuenta_ok = CuentaBancaria::TYPE_ACCOUNT_5;
                                $cuentabancaria = [
                                    'n_cuenta'      => $n_cuenta,
                                    'banco_id'      => $banco->id,
                                ];
                                break;

                            case 'Cuenta Corriente':
                                $tipo_cuenta_ok = CuentaBancaria::TYPE_ACCOUNT_3;
                                $cuentabancaria = [
                                    'n_cuenta'      => $n_cuenta,
                                    'banco_id'      => $banco->id,
                                ];
                                break;

                            case 'Cuenta Rut':
                                $tipo_cuenta_ok = CuentaBancaria::TYPE_ACCOUNT_4;
                                $cuentabancaria = [
                                    'n_cuenta'      => $user->rut,
                                    'banco_id'      => $banco->id,
                                ];
                                break;

                            case 'Cuenta Vista (Cuenta Corriente)':
                                $tipo_cuenta_ok = CuentaBancaria::TYPE_ACCOUNT_1;
                                $cuentabancaria = [
                                    'n_cuenta'      => $n_cuenta,
                                    'banco_id'      => $banco->id,
                                ];
                                break;

                            default:
                                $tipo_cuenta_ok = NULL;
                                $cuentabancaria = [
                                    'n_cuenta'      => $n_cuenta,
                                    'banco_id'      => $banco->id,
                                ];
                                break;
                        }
                        $cuentabancaria['tipo_cuenta'] = $tipo_cuenta_ok;
                        $cuentabancaria['user_id'] = $user->id;
                        $new_cuenta = CuentaBancaria::create($cuentabancaria);
                        if($new_cuenta){
                            Log::info('cuenta creada!');
                        }
                        /* array_push($cuentas, $cuentabancaria); */

                        /* Log::info($cuentabancaria); */
                    } else {
                        if (!$banco) {
                            /* Log::info("No se encuentra banco {$row['banco']}"); */
                        }
                    }
                } catch (\Exception $error) {
                    Log::info($error->getMessage());
                }
            }
            $total = count($cuentas);
            Log::info($total);
            DB::commit();
        } catch (\Exception $error) {
            DB::rollback();
            Log::info($error->getMessage());
        }
    }
}
