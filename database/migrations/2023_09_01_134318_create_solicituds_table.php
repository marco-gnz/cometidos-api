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
            $table->time('hora_inicio')->nullable();
            $table->time('hora_termino')->nullable();
            $table->integer('total_horas_cometido')->nullable();
            $table->integer('total_dias_cometido')->nullable();
            $table->text('detalle_lugar_cometido')->nullable();
            $table->boolean('derecho_pasajes')->nullable();
            $table->text('detalle_pasajes')->nullable();
            $table->unsignedSmallInteger('medio_transporte')->nullable();
            $table->boolean('derecho_pago')->default(0);
            $table->text('actividades_realizadas')->nullable();
            $table->integer('valor_cometido_diario')->nullable()->default(0);
            $table->integer('valor_cometido_parcial')->nullable()->default(0);
            $table->integer('valor_pasaje')->nullable()->default(0);
            $table->integer('valor_total')->nullable()->default(0);
            $table->unsignedSmallInteger('last_status')->default(1);

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
