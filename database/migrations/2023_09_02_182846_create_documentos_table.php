<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->nullable();
            $table->string('url');
            $table->unsignedSmallInteger('model')->nullable();
            $table->string('nombre');
            $table->integer('size');
            $table->string('format');
            $table->string('extension')->nullable();
            $table->boolean('is_valid')->nullable();

            $table->foreign('solicitud_id')->references('id')->on('solicituds')->onDelete('cascade');
            $table->unsignedBigInteger('solicitud_id')->nullable();

            $table->foreign('proceso_rendicion_gasto_id')->references('id')->on('proceso_rendicion_gastos')->onDelete('cascade');
            $table->unsignedBigInteger('proceso_rendicion_gasto_id')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();

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
        Schema::dropIfExists('documentos');
    }
}
