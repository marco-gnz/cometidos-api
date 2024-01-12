<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoliucitudCalculosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('soliucitud_calculos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();
            $table->integer('n_dias_40')->nullable();
            $table->integer('n_dias_100')->nullable();
            $table->integer('valor_dia_40')->nullable();
            $table->integer('valor_dia_100')->nullable();
            $table->integer('monto_40')->nullable();
            $table->integer('monto_100')->nullable();
            $table->integer('monto_total')->nullable();

            $table->foreign('solicitud_id')->references('id')->on('solicituds')->onDelete('cascade');
            $table->unsignedBigInteger('solicitud_id')->nullable();

            $table->foreign('ley_id')->references('id')->on('leys');
            $table->unsignedBigInteger('ley_id')->nullable();

            $table->foreign('grado_id')->references('id')->on('grados');
            $table->unsignedBigInteger('grado_id')->nullable();

            $table->unsignedBigInteger('user_id_by')->nullable();
            $table->foreign('user_id_by')->references('id')->on('users');
            $table->dateTime('fecha_by_user', 0)->nullable();

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
        Schema::dropIfExists('soliucitud_calculos');
    }
}
