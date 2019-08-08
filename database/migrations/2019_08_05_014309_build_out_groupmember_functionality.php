<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BuildOutGroupmemberFunctionality extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_members', function (Blueprint $table) {
            $table->string('shortname')->after('name')
                ->nullable()
                ->default(null);
            $table->string('timezone')->default("America/New_York");
            $table->boolean('self_created')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('group_members', function (Blueprint $table) {
            $table->dropColumn('shortname');
            $table->dropColumn('timezone');
            $table->dropColumn('self_created');
        });
    }
}
