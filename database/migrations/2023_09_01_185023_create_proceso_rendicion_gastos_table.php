<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcesoRendicionGastosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proceso_rendicion_gastos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->nullable();
            $table->string('n_folio')->unique()->nullable();
            $table->integer('n_rendicion')->default(0);
            $table->date('fecha_pago')->nullable();
            $table->unsignedSmallInteger('status')->default(0);
            $table->text('observacion')->nullable();
            $table->foreign('solicitud_id')->references('id')->on('solicituds')->onDelete('cascade');
            $table->unsignedBigInteger('solicitud_id')->nullable();

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
        Schema::dropIfExists('proceso_rendicion_gastos');
    }
}
