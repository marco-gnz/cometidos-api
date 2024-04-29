<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstadoProcesoRendicionGastosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estado_proceso_rendicion_gastos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->nullable();
            $table->unsignedSmallInteger('status')->default(0);
            $table->integer('posicion_firma')->nullable();
            $table->text('observacion')->nullable();
            $table->string('ip_address')->nullable();
            $table->boolean('is_subrogante')->default(0);

            $table->foreign('p_rendicion_gasto_id')->references('id')->on('proceso_rendicion_gastos')->onDelete('cascade');
            $table->unsignedBigInteger('p_rendicion_gasto_id')->nullable();

            $table->foreign('role_id')->references('id')->on('roles');
            $table->unsignedBigInteger('role_id')->nullable();

            $table->unsignedBigInteger('user_id_by')->nullable();
            $table->foreign('user_id_by')->references('id')->on('users');
            $table->dateTime('fecha_by_user', 0)->nullable();

            $table->unsignedBigInteger('user_id_update')->nullable();
            $table->foreign('user_id_update')->references('id')->on('users');
            $table->dateTime('fecha_by_user_update', 0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('estado_proceso_rendicion_gastos');
    }
}
