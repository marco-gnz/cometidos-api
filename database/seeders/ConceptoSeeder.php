<?php

namespace Database\Seeders;

use App\Models\Concepto;
use App\Models\ConceptoEstablecimiento;
use App\Models\Establecimiento;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ConceptoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            $nombre_1 = 'ABASTECIMIENTO';
            $nombre_2 = 'CAPACITACIÓN FINANCIAMIENTO CENTRALIZADO';

            $avion = new Concepto();
            $avion->nombre = $nombre_1;
            $avion->descripcion = 'Cometidos en las que se marcó la opción Avión en medio de transporte, serán copiados a los siguientes usuarios.';
            $avion->save();

            $capacitacion = new Concepto();
            $capacitacion->nombre = $nombre_2;
            $capacitacion->descripcion = "Cometidos en las que se marcó la opción $nombre_2, se asociará como firmante el primer usuario.";
            $capacitacion->save();

            $conceptos = Concepto::all();
            $establecimientos = Establecimiento::all();

            foreach ($conceptos as $concepto) {
                if ($concepto->nombre === $nombre_1) {
                    foreach ($establecimientos as $establecimiento) {
                        $concepto_establecimiento = ConceptoEstablecimiento::create([
                            'concepto_id'           => $concepto->id,
                            'establecimiento_id'    => $establecimiento->id
                        ]);
                        if ($concepto_establecimiento) {
                            $ruts = ['11927382', '10426233', '16831875'];
                            $usuarios = User::whereIn('rut', $ruts)->get();
                            foreach ($usuarios as $key => $usuario) {
                                $concepto_establecimiento->funcionarios()->attach($usuario->id, ['posicion' => $key + 1]);
                            }
                        }
                    }
                } else if ($concepto->nombre === $nombre_2) {
                    foreach ($establecimientos as $establecimiento) {
                        $concepto_establecimiento = ConceptoEstablecimiento::create([
                            'concepto_id'           => $concepto->id,
                            'establecimiento_id'    => $establecimiento->id
                        ]);
                        if ($concepto_establecimiento) {
                            $ruts = ['6732846', '11711212'];
                            $usuarios = User::whereIn('rut', $ruts)->get();
                            foreach ($usuarios as $key => $usuario) {
                                $concepto_establecimiento->funcionarios()->attach($usuario->id, ['posicion' => $key + 1]);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $error) {
            Log::info($error->getMessage());
        }
    }
}
