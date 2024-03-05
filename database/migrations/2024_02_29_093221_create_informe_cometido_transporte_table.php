<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInformeCometidoTransporteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('informe_cometido_transporte', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('transporte_id');
            $table->unsignedInteger('informe_cometido_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('informe_cometido_transporte');
    }
}
