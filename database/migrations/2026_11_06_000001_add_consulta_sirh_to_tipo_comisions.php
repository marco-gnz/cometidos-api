<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddConsultaSirhToTipoComisions extends Migration
{
    public function up()
    {
        Schema::table('tipo_comisions', function (Blueprint $table) {
            $table->string('cod_web_service')->nullable();
            $table->unsignedTinyInteger('metodo_consulta')->default(2);
        });

        // No depender de IDs ni eliminar tipos usados por solicitudes históricas.
        foreach ([
            'ESTUDIOS' => '2',
            'CAPACITACIÓN' => '3',
            'DE SERVICIO' => '1',
            'SERVICIO' => '1',
            'CAPACITACIÓN FINANCIAMIENTO CENTRALIZADO' => '3',
        ] as $nombre => $codigo) {
            DB::table('tipo_comisions')->whereRaw('UPPER(TRIM(nombre)) = ?', [$nombre])
                ->update(['cod_web_service' => $codigo]);
        }
    }

    public function down()
    {
        Schema::table('tipo_comisions', function (Blueprint $table) {
            $table->dropColumn(['cod_web_service', 'metodo_consulta']);
        });
    }
}
