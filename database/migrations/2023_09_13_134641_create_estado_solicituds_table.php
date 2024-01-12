<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstadoSolicitudsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estado_solicituds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('status');
            $table->unsignedSmallInteger('motivo_rechazo')->nullable();
            $table->text('observacion')->nullable();
            $table->integer('posicion_firma')->nullable();
            $table->integer('posicion_next_firma')->nullable();
            $table->text('history_solicitud')->nullable();
            $table->boolean('reasignacion')->default(0); //el admin al reasignar, se debe crear un nuevo registro en estado_solicituds, con reasignacion en true
            $table->boolean('reasignado')->default(0); // registro anterior creado, con valor reasignado en true

            $table->foreign('solicitud_id')->references('id')->on('solicituds');
            $table->unsignedBigInteger('solicitud_id')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('role_id')->references('id')->on('roles');
            $table->unsignedBigInteger('role_id')->nullable();

            $table->foreign('user_firmante_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_firmante_id')->nullable();

            $table->foreign('role_firmante_id')->references('id')->on('roles');
            $table->unsignedBigInteger('role_firmante_id')->nullable();

            $table->string('ip_address')->nullable();

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
        Schema::dropIfExists('estado_solicituds');
    }
}
