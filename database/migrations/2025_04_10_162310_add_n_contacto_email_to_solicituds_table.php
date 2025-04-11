<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNContactoEmailToSolicitudsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('solicituds', function (Blueprint $table) {
            $table->string('n_contacto')->nullable()->after('is_reasignada');
            $table->string('email')->nullable()->after('n_contacto');
            $table->foreign('nacionalidad_id')->references('id')->on('nacionalidads')->after('tipo_comision_id');
            $table->unsignedBigInteger('nacionalidad_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('solicituds', function (Blueprint $table) {
            $table->dropColumn('n_contacto');
            $table->dropColumn('email');
        });
    }
}
