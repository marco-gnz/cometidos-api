<?php

namespace Database\Seeders;

use App\Models\Contrato;
use App\Models\Convenio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Faker\Factory as Faker;

class TestConveniosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            $contratos = Contrato::orderBy('id', 'DESC')->get()->take(20);

            foreach ($contratos as $contrato) {
                $faker = Faker::create();
                $convenio = new Convenio();
                $convenio->fecha_inicio         = '2024-01-01';
                $convenio->fecha_termino        = '2024-12-31';
                $convenio->fecha_resolucion     = '2024-01-12';
                $convenio->anio                 = '2024';
                $convenio->n_resolucion         = rand();
                $convenio->n_viatico_mensual    = rand(1, 9);
                $convenio->user_id              = $contrato->funcionario->id;
                $convenio->estamento_id         = $contrato->estamento_id;
                $convenio->ley_id               = $contrato->ley_id;
                $convenio->establecimiento_id   = $contrato->establecimiento_id;
                $convenio->ilustre_id           = rand(1, 4);
                $convenio->observacion          = $faker->paragraph;
                $convenio->save();
            }
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }
}
