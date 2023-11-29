<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRendicionGastosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rendicion_gastos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->nullable();
            $table->boolean('rinde_gasto')->default(0);
            $table->integer('mount')->nullable();
            $table->integer('mount_real')->nullable();
            $table->unsignedSmallInteger('last_status')->default(0);
            $table->boolean('rinde_gastos_servicio')->default(0)->nullable();

            $table->foreign('proceso_rendicion_gasto_id')->references('id')->on('proceso_rendicion_gastos')->onDelete('cascade');
            $table->unsignedBigInteger('proceso_rendicion_gasto_id')->nullable();

            $table->foreign('actividad_gasto_id')->references('id')->on('actividad_gastos');
            $table->unsignedBigInteger('actividad_gasto_id')->nullable();

            $table->foreign('item_presupuestario_id')->references('id')->on('item_presupuestarios');
            $table->unsignedBigInteger('item_presupuestario_id')->nullable();

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
        Schema::dropIfExists('rendicion_gastos');
    }
}
