<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActividadGastoSolicitudTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('actividad_gasto_solicitud', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('actividad_gasto_id');
            $table->unsignedInteger('solicitud_id');
            $table->integer('mount')->nullable();
            $table->boolean('status')->default(0)->nullable();
            $table->boolean('status_admin')->default(1)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('actividad_gasto_solicitud');
    }
}
