<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSolicitudFirmantesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('solicitud_firmantes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->nullable();
            $table->integer('posicion_firma')->default(1);
            $table->boolean('is_reasignado')->default(0);
            $table->boolean('status')->default(1);

            $table->foreign('solicitud_id')->references('id')->on('solicituds')->onDelete('cascade');
            $table->unsignedBigInteger('solicitud_id')->nullable();

            $table->foreign('grupo_id')->references('id')->on('grupos');
            $table->unsignedBigInteger('grupo_id')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('role_id')->references('id')->on('roles');
            $table->unsignedBigInteger('role_id')->nullable();

            $table->unsignedBigInteger('user_id_by')->nullable();
            $table->foreign('user_id_by')->references('id')->on('users');
            $table->dateTime('fecha_by_user', 0)->nullable();

            $table->unsignedBigInteger('user_id_update')->nullable();
            $table->foreign('user_id_update')->references('id')->on('users');
            $table->dateTime('fecha_by_user_update', 0)->nullable();

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
        Schema::dropIfExists('solicitud_firmantes');
    }
}
