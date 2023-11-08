<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSolicitudsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('solicituds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->nullable();
            $table->string('codigo')->unique()->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();
            $table->time('hora_llegada')->nullable();
            $table->time('hora_salida')->nullable();
            $table->boolean('derecho_pago')->default(0);
            $table->text('actividad_realizada')->nullable();
            $table->boolean('gastos_alimentacion')->default(0)->nullable();
            $table->boolean('gastos_alojamiento')->default(0)->nullable();
            $table->boolean('pernocta_lugar_residencia')->default(0)->nullable();
            $table->integer('n_dias_40')->nullable();
            $table->integer('n_dias_100')->nullable();
            $table->text('observacion_gastos')->nullable();
            $table->unsignedSmallInteger('last_status')->default(0);


            $table->integer('total_dias_cometido')->nullable();
            $table->integer('total_horas_cometido')->nullable();
            $table->integer('valor_cometido_diario')->nullable();
            $table->integer('valor_cometido_parcial')->nullable();
            $table->integer('valor_pasaje')->nullable();
            $table->integer('valor_total')->nullable();


            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('grupo_id')->references('id')->on('grupos');
            $table->unsignedBigInteger('grupo_id')->nullable();

            $table->foreign('departamento_id')->references('id')->on('departamentos');
            $table->unsignedBigInteger('departamento_id')->nullable();

            $table->foreign('sub_departamento_id')->references('id')->on('sub_departamentos');
            $table->unsignedBigInteger('sub_departamento_id')->nullable();

            $table->foreign('establecimiento_id')->references('id')->on('establecimientos');
            $table->unsignedBigInteger('establecimiento_id')->nullable();

            $table->foreign('motivo_id')->references('id')->on('motivos');
            $table->unsignedBigInteger('motivo_id')->nullable();

            $table->foreign('escala_id')->references('id')->on('escalas');
            $table->unsignedBigInteger('escala_id')->nullable();

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
        Schema::dropIfExists('solicituds');
    }
}
