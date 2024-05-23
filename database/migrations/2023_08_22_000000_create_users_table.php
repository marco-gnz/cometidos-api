<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->integer('rut')->unique();
            $table->string('dv', 1);
            $table->string('rut_completo')->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('nombre_completo');
            $table->integer('n_cargo')->default(0);
            $table->boolean('estado')->default(1);
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('telefono', 12)->nullable();
            $table->string('anexo', 12)->nullable();
            $table->boolean('is_firmante')->default(0);
            $table->boolean('is_subrogante')->default(0);

            $table->boolean('is_solicitud')->default(1);
            $table->boolean('is_informe')->default(1);
            $table->boolean('is_rendicion')->default(1);

            $table->unsignedBigInteger('usuario_add_id')->nullable();
            $table->foreign('usuario_add_id')->references('id')->on('users');
            $table->dateTime('fecha_add', 0)->nullable();

            $table->unsignedBigInteger('usuario_update_id')->nullable();
            $table->foreign('usuario_update_id')->references('id')->on('users');
            $table->dateTime('fecha_update', 0)->nullable();

            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
