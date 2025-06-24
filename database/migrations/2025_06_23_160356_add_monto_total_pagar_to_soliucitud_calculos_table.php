<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMontoTotalPagarToSoliucitudCalculosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('soliucitud_calculos', function (Blueprint $table) {
            $table->integer('monto_total_pagar')->nullable()->after('monto_total');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('soliucitud_calculos', function (Blueprint $table) {
            $table->dropColumn('monto_total_pagar');
        });
    }
}
