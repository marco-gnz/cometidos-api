<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSirhConsultasTable extends Migration
{
    public function up()
    {
        Schema::create('sirh_consultas', function (Blueprint $table) {
            $table->string('clave', 64)->primary();
            $table->longText('resultado')->nullable();
            $table->unsignedBigInteger('vigente_hasta')->default(0);
            $table->unsignedBigInteger('bloqueado_hasta')->default(0);
            $table->string('token', 64)->nullable();
        });
    }

    public function down() { Schema::dropIfExists('sirh_consultas'); }
}
