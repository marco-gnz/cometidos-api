<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSolicitudSirhLoadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('solicitud_sirh_loads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('load_sirh')->default(0);
            $table->string('ip_address')->nullable();

            $table->foreign('solicitud_id')->references('id')->on('solicituds');
            $table->unsignedBigInteger('solicitud_id')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();

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
        Schema::dropIfExists('solicitud_sirh_loads');
    }
}
