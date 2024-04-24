<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalculoAjustesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calculo_ajustes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->nullable();
            $table->unsignedSmallInteger('tipo_ajuste');
            $table->decimal('n_dias_40')->nullable();
            $table->decimal('n_dias_100')->nullable();
            $table->decimal('monto_40')->nullable();
            $table->decimal('monto_100')->nullable();
            $table->text('observacion')->nullable();
            $table->boolean('active')->default(1);
            $table->string('ip_address')->nullable();

            $table->foreign('soliucitud_calculo_id')->references('id')->on('soliucitud_calculos')->onDelete('cascade');
            $table->unsignedBigInteger('soliucitud_calculo_id')->nullable();

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
        Schema::dropIfExists('calculo_ajustes');
    }
}
