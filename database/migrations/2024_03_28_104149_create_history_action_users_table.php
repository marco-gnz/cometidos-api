<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoryActionUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('history_action_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('type');
            $table->text('data_old')->nullable();
            $table->text('data_new')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('observacion')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedBigInteger('send_to_user_id')->nullable();
            $table->foreign('send_to_user_id')->references('id')->on('users');

            $table->unsignedBigInteger('user_id_by')->nullable();
            $table->foreign('user_id_by')->references('id')->on('users');

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
        Schema::dropIfExists('history_action_users');
    }
}
