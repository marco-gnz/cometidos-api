<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReasignacionSolicitudTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reasignacion_solicitud', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('reasignacion_id');
            $table->unsignedInteger('solicitud_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reasignacion_solicitud');
    }
}
