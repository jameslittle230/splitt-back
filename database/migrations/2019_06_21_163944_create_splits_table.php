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
            $table->bigIncrements('id');
            $table->timestamps();
            $table->unsignedBigInteger('transaction');
            $table->foreign('transaction')->references('id')->on('transactions');
            $table->integer('amount');
            $table->integer('percentage');
            $table->unsignedBigInteger('debtor');
            $table->foreign('debtor')->references('id')->on('group_members');
            $table->boolean('reconciled')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('splits');
    }
}
