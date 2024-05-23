<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContratosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();
            $table->date('fecha_alejamiento')->nullable();
            $table->boolean('alejamiento')->default(0);
            $table->boolean('status')->default(1);

            $table->unsignedBigInteger('ley_id')->nullable();
            $table->foreign('ley_id')->references('id')->on('leys');

            $table->unsignedBigInteger('estamento_id')->nullable();
            $table->foreign('estamento_id')->references('id')->on('estamentos');

            $table->unsignedBigInteger('cargo_id')->nullable();
            $table->foreign('cargo_id')->references('id')->on('cargos');

            $table->unsignedBigInteger('departamento_id')->nullable();
            $table->foreign('departamento_id')->references('id')->on('departamentos');

            $table->unsignedBigInteger('sub_departamento_id')->nullable();
            $table->foreign('sub_departamento_id')->references('id')->on('sub_departamentos');

            $table->unsignedBigInteger('establecimiento_id')->nullable();
            $table->foreign('establecimiento_id')->references('id')->on('establecimientos');

            $table->unsignedBigInteger('grado_id')->nullable();
            $table->foreign('grado_id')->references('id')->on('grados');

            $table->unsignedBigInteger('hora_id')->nullable();
            $table->foreign('hora_id')->references('id')->on('horas');

            $table->unsignedBigInteger('calidad_id')->nullable();
            $table->foreign('calidad_id')->references('id')->on('calidads');

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedBigInteger('usuario_add_id')->nullable();
            $table->foreign('usuario_add_id')->references('id')->on('users');
            $table->dateTime('fecha_add', 0)->nullable();

            $table->unsignedBigInteger('usuario_update_id')->nullable();
            $table->foreign('usuario_update_id')->references('id')->on('users');
            $table->dateTime('fecha_update', 0)->nullable();

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
        Schema::dropIfExists('contratos');
    }
}
