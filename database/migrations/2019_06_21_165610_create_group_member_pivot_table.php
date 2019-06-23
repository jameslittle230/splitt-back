<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupMemberPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_groupmember', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();

            $table->uuid('group');
            $table->foreign('group')->references('id')->on('groups');

            $table->uuid('groupmember');
            $table->foreign('groupmember')->references('id')->on('group_members');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('group_groupmember', function(Blueprint $table) {
            $table->dropForeign('groupmember');
            $table->dropForeign('group');
        });

        Schema::dropIfExists('group_groupmember');
    }
}
