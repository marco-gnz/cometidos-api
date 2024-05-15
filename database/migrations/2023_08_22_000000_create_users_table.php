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

            $table->unsignedBigInteger('ley_id')->nullable();
            $table->foreign('ley_id')->references('id')->on('leys');

            $table->unsignedBigInteger('estamento_id')->nullable();
            $table->foreign('estamento_id')->references('id')->on('estamentos');

            $table->unsignedBigInteger('cargo_id')->nullable();
            $table->foreign('cargo_id')->references('id')->on('cargos');

            $table->unsignedBigInteger('departamento_id')->nullable();
            $table->foreign('departamento_id')->references('id')->on('departamentos');

            $table->unsignedBigInteger('sub_departamento_id')->nullable();
            $table->foreign('sub_departamento_id')->references('id')->on('sub_departamentos');

            $table->unsignedBigInteger('establecimiento_id')->nullable();
            $table->foreign('establecimiento_id')->references('id')->on('establecimientos');

            $table->unsignedBigInteger('grado_id')->nullable();
            $table->foreign('grado_id')->references('id')->on('grados');

            $table->unsignedBigInteger('hora_id')->nullable();
            $table->foreign('hora_id')->references('id')->on('horas');

            $table->unsignedBigInteger('calidad_id')->nullable();
            $table->foreign('calidad_id')->references('id')->on('calidads');

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
