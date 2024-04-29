<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReasignacionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reasignacions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();

            $table->foreign('user_ausente_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_ausente_id')->nullable();

            $table->foreign('user_subrogante_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_subrogante_id')->nullable();

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
        Schema::dropIfExists('reasignacions');
    }
}
