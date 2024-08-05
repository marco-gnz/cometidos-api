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
            $table->integer('posicion_firma_s')->nullable();
            $table->integer('posicion_firma_r_s')->nullable();
            $table->text('history_solicitud_old')->nullable();
            $table->text('history_solicitud_new')->nullable();
            $table->boolean('is_reasignado')->default(0); // registro anterior creado, con valor reasignado en true
            $table->boolean('is_subrogante')->default(0);
            $table->boolean('movimiento_system')->default(0);

            $table->foreign('solicitud_id')->references('id')->on('solicituds');
            $table->unsignedBigInteger('solicitud_id')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('s_role_id')->references('id')->on('roles');
            $table->unsignedBigInteger('s_role_id')->nullable();

            $table->foreign('firmante_id')->references('id')->on('solicitud_firmantes')->onDelete('cascade');
            $table->unsignedBigInteger('firmante_id')->nullable();

            $table->foreign('s_firmante_id')->references('id')->on('solicitud_firmantes')->onDelete('cascade');
            $table->unsignedBigInteger('s_firmante_id')->nullable();

            $table->foreign('r_s_role_id')->references('id')->on('roles');
            $table->unsignedBigInteger('r_s_role_id')->nullable();

            $table->foreign('r_s_user_id')->references('id')->on('users');
            $table->unsignedBigInteger('r_s_user_id')->nullable(); //r=reasignado s=solicitud

            $table->foreign('r_s_firmante_id')->references('id')->on('solicitud_firmantes')->onDelete('cascade');
            $table->unsignedBigInteger('r_s_firmante_id')->nullable(); //r=reasignado s=solicitud

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
