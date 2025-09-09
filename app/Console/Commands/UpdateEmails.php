<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:emails {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar emails users';

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

            foreach ($data as $key => $row) {
                try {
                    $rut                    = $row['rut'];
                    $dv                     = $row['dv'];
                    $email                  = $row['email'];

                    $user                   = User::where('rut', $rut)->first();

                    if($user){
                        if($user->email !== $email){
                            $update = $user->update([
                                'email' => $email
                            ]);
                            if ($update) {
                                Log::info('usuario modificado');
                            }
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
