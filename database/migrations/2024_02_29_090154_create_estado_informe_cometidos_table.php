<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstadoInformeCometidosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estado_informe_cometidos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->nullable();
            $table->unsignedSmallInteger('status')->default(0);
            $table->text('observacion')->nullable();
            $table->string('ip_address')->nullable();

            $table->foreign('informe_cometido_id')->references('id')->on('informe_cometidos')->onDelete('cascade');
            $table->unsignedBigInteger('informe_cometido_id')->nullable();

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
        Schema::dropIfExists('estado_informe_cometidos');
    }
}
