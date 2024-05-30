<?php

namespace Database\Seeders;

use App\Models\Configuration;
use App\Models\Establecimiento;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Configuration::truncate();
        $establecimientos = Establecimiento::all();

        foreach ($establecimientos as $establecimiento) {
            $configuration = new Configuration();
            $configuration->clave = 'informecometido.dias_atraso';
            $configuration->valor = '2';
            $configuration->tipo  = 'dias';
            $configuration->descripcion = "Días de atraso informe cometido";
            $configuration->establecimiento_id = $establecimiento->id;
            $configuration->save();

            $configuration = new Configuration();
            $configuration->clave = 'informecometido.total_pendiente';
            $configuration->valor = '2';
            $configuration->tipo  = 'total';
            $configuration->descripcion = 'Total pendientes informe cometido';
            $configuration->establecimiento_id = $establecimiento->id;
            $configuration->save();

            $configuration = new Configuration();
            $configuration->clave = 'solicitud.dias_atraso_actividad';
            $configuration->valor = '5';
            $configuration->tipo  = 'dias';
            $configuration->descripcion = "Total días de NO actividad al editar solicitud";
            $configuration->establecimiento_id = $establecimiento->id;
            $configuration->save();

            $configuration = new Configuration();
            $configuration->clave = 'info.vistos';
            $configuration->valor = 'VISTOS: Las necesidades de servicio; Art. 78 y Art 98-Letra E, ambos del DFL N°29/2004 del Ministerio de Hacienda; DFL N°1/2005 del Ministerio de Salud Fija el
        texto refundido, coordinado y sistematizado del DL 2763/1979 que crea los Servicios de Salud; DS. 140/2004 Reglamento Orgánico de los Servicios Salud; DFL
        N°262/1977 del Ministerio de Hacienda, Reglamento de Viáticos dentro del Territorio Nacional; Circulares N°03/2012 y N°02/2015 ambas del Servicio Salud
        Osorno; Resolución 1600/2008 y 06/2019, ambas de la Contraloría General de la República; Decreto Exento N°65/2022 del Ministerio de Salud; Resolución Exenta
        N°556/2021 del Servicio de Salud Osorno; Resolución Exenta N°546/2021 del Servicio Salud Osorno; dicto la siguiente.';
            $configuration->tipo  = 'texto';
            $configuration->descripcion = "Vistos";
            $configuration->establecimiento_id = $establecimiento->id;
            $configuration->save();

            $configuration = new Configuration();
            $configuration->clave = 'solicitud.dias_plazo_avion';
            $configuration->valor = '10';
            $configuration->tipo  = 'dias';
            $configuration->descripcion = "Total días de plazo avión";
            $configuration->establecimiento_id = $establecimiento->id;
            $configuration->save();
        }

    }
}
