<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NullableResults extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->integer('homeGoals')->nullable()->change();
            $table->integer('awayGoals')->nullable()->change();
            $table->integer('extraTimeHomeGoals')->nullable()->change();
            $table->integer('extraTimeAwayGoals')->nullable()->change();
            $table->integer('penaltiesHome')->nullable()->change();
            $table->integer('penaltiesAway')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->integer('homeGoals')->default(0)->change();
            $table->integer('awayGoals')->default(0)->change();
            $table->integer('extraTimeHomeGoals')->default(0)->change();
            $table->integer('extraTimeAwayGoals')->default(0)->change();
            $table->integer('penaltiesHome')->default(0)->change();
            $table->integer('penaltiesAway')->default(0)->change();
        });
    }
}
