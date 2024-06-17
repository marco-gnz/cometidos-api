<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemPresupuestarioUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_presupuestario_users', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('item_presupuestario_id')->nullable();
            $table->foreign('item_presupuestario_id')->references('id')->on('item_presupuestarios');

            $table->unsignedBigInteger('calidad_id')->nullable();
            $table->foreign('calidad_id')->references('id')->on('calidads');

            $table->unsignedBigInteger('ley_id')->nullable();
            $table->foreign('ley_id')->references('id')->on('leys');

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
        Schema::dropIfExists('item_presupuestario_users');
    }
}
