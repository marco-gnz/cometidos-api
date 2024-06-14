<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConveniosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('convenios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->nullable();
            $table->string('codigo')->unique()->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();
            $table->date('fecha_resolucion')->nullable();
            $table->string('n_resolucion')->nullable();
            $table->integer('n_viatico_mensual');
            $table->unsignedSmallInteger('tipo_convenio')->nullable();
            $table->year('anio')->nullable();
            $table->text('observacion')->nullable();
            $table->boolean('active')->default(1);

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedBigInteger('estamento_id')->nullable();
            $table->foreign('estamento_id')->references('id')->on('estamentos');

            $table->unsignedBigInteger('ley_id')->nullable();
            $table->foreign('ley_id')->references('id')->on('leys');

            $table->unsignedBigInteger('establecimiento_id')->nullable();
            $table->foreign('establecimiento_id')->references('id')->on('establecimientos');

            $table->unsignedBigInteger('ilustre_id')->nullable();
            $table->foreign('ilustre_id')->references('id')->on('ilustres');

            $table->unsignedBigInteger('user_id_by')->nullable();
            $table->foreign('user_id_by')->references('id')->on('users');

            $table->unsignedBigInteger('user_id_update')->nullable();
            $table->foreign('user_id_update')->references('id')->on('users');

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
        Schema::dropIfExists('convenios');
    }
}
