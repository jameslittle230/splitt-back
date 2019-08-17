<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeReconciledColumnToReconciliations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('splits', function (Blueprint $table) {
            $table->renameColumn('reconciled', 'reconciliation');
            $table->foreign('transaction')->references('id')->on('transactions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('splits', function (Blueprint $table) {
            $table->renameColumn('reconciliation', 'reconciled');
        });
    }
}
