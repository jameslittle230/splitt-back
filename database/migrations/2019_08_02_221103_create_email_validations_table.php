<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmailValidationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_validations', function (Blueprint $table) {
            $table->uuid('id');
            $table->timestamps();
            $table->uuid('group_member');
            $table->foreign('group_member')->references('id')->on('group_members');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('email_validations', function(Blueprint $table) {
            $table->dropForeign('group_member');
        });
        Schema::dropIfExists('email_validations');
    }
}
