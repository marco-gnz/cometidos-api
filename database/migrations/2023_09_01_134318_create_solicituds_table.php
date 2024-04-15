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
            $table->string('correlativo')->nullable();
            $table->string('codigo')->unique()->nullable();
            $table->boolean('fijada')->default(0);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();
            $table->time('hora_llegada')->nullable();
            $table->time('hora_salida')->nullable();
            $table->boolean('utiliza_transporte')->default(1);
            $table->boolean('viaja_acompaniante')->default(0);
            $table->boolean('alimentacion_red')->default(0);
            $table->boolean('derecho_pago')->default(0);
            $table->boolean('afecta_convenio')->nullable();
            $table->text('actividad_realizada')->nullable();
            $table->boolean('gastos_alimentacion')->default(0)->nullable();
            $table->boolean('gastos_alojamiento')->default(0)->nullable();
            $table->boolean('pernocta_lugar_residencia')->default(0)->nullable();
            $table->boolean('is_reasignada')->default(0)->nullable();

            $table->integer('n_dias_40')->nullable();
            $table->integer('n_dias_100')->nullable();
            $table->text('observacion_gastos')->nullable();
            $table->text('vistos')->nullable();
            $table->unsignedSmallInteger('status')->default(0);
            $table->unsignedSmallInteger('last_status')->default(0);
            $table->date('fecha_resolucion')->nullable();
            $table->string('n_resolucion')->unique()->nullable();
            $table->unsignedSmallInteger('tipo_resolucion')->default(0);
            $table->unsignedSmallInteger('jornada')->default(0);
            $table->boolean('dentro_pais')->default(0);
            $table->integer('n_cargo_user')->default(0);
            $table->boolean('calculo_aplicado')->default(0);
            $table->integer('total_dias_cometido')->nullable();
            $table->integer('total_horas_cometido')->nullable();
            $table->integer('posicion_firma_actual')->default(0);
            $table->integer('total_firmas')->default(0)->nullable();
            $table->integer('dias_permitidos')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('grupo_id')->references('id')->on('grupos');
            $table->unsignedBigInteger('grupo_id')->nullable();

            $table->foreign('departamento_id')->references('id')->on('departamentos');
            $table->unsignedBigInteger('departamento_id')->nullable();

            $table->foreign('sub_departamento_id')->references('id')->on('sub_departamentos');
            $table->unsignedBigInteger('sub_departamento_id')->nullable();

            $table->foreign('ley_id')->references('id')->on('leys');
            $table->unsignedBigInteger('ley_id')->nullable();

            $table->unsignedBigInteger('estamento_id')->nullable();
            $table->foreign('estamento_id')->references('id')->on('estamentos');

            $table->foreign('convenio_id')->references('id')->on('convenios');
            $table->unsignedBigInteger('convenio_id')->nullable();

            $table->foreign('grado_id')->references('id')->on('grados');
            $table->unsignedBigInteger('grado_id')->nullable();

            $table->foreign('establecimiento_id')->references('id')->on('establecimientos');
            $table->unsignedBigInteger('establecimiento_id')->nullable();

            $table->foreign('cargo_id')->references('id')->on('cargos');
            $table->unsignedBigInteger('cargo_id')->nullable();

            $table->unsignedBigInteger('hora_id')->nullable();
            $table->foreign('hora_id')->references('id')->on('horas');

            $table->foreign('escala_id')->references('id')->on('escalas');
            $table->unsignedBigInteger('escala_id')->nullable();

            $table->unsignedBigInteger('calidad_id')->nullable();
            $table->foreign('calidad_id')->references('id')->on('calidads');

            $table->foreign('tipo_comision_id')->references('id')->on('tipo_comisions');
            $table->unsignedBigInteger('tipo_comision_id')->nullable();

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
