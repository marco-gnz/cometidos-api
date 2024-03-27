<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstadoRendicionGastosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estado_rendicion_gastos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->nullable();
            $table->unsignedSmallInteger('status')->default(0);
            $table->boolean('is_updated_mount')->default(0);
            $table->text('observacion')->nullable();
            $table->text('rendicion_old')->nullable();
            $table->text('rendicion_new')->nullable();
            $table->string('ip_address')->nullable();

            $table->foreign('rendicion_gasto_id')->references('id')->on('rendicion_gastos')->onDelete('cascade');
            $table->unsignedBigInteger('rendicion_gasto_id')->nullable();

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
        Schema::dropIfExists('estado_rendicion_gastos');
    }
}
