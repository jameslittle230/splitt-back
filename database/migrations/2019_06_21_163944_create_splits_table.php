<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSplitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('splits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
            
            $table->uuid('transaction');
            $table->foreign('transaction')->references('id')->on('transactions');
            
            $table->integer('amount');
            $table->double('percentage', 6, 2);
            
            $table->uuid('debtor');
            $table->foreign('debtor')->references('id')->on('group_members');

            $table->uuid('reconciliation')->nullable();
            $table->foreign('reconciliation')->references('id')->on('reconciliations');
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
            $table->dropForeign('transaction');
            $table->dropForeign('debtor');
        });

        Schema::dropIfExists('splits');
    }
}
