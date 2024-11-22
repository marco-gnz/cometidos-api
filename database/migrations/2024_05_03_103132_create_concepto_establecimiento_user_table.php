<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConceptoEstablecimientoUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('concepto_establecimiento_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('concepto_establecimiento_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('role_id');
            $table->integer('posicion');
            $table->boolean('active')->default(1);
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
        Schema::dropIfExists('concepto_establecimiento_user');
    }
}
