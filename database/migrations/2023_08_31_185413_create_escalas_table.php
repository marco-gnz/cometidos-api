<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEscalasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('escalas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique()->nullable();
            $table->year('ano');
            $table->integer('valor_dia')->default(0);
            $table->integer('valor_noche')->default(0);

            $table->foreign('ley_id')->references('id')->on('leys');
            $table->unsignedBigInteger('ley_id')->nullable();

            $table->foreign('grado_id')->references('id')->on('grados');
            $table->unsignedBigInteger('grado_id')->nullable();

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
        Schema::dropIfExists('escalas');
    }
}
