<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Initial extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->integer('id');
            $table->string('name', 255);
            $table->string('code', 10);
            $table->string('logo', 255);
        });
        
        Schema::create('fixtures', function (Blueprint $table) {
            $table->integer('id');
            $table->integer('homeTeamId');
            $table->integer('awayTeamId');
            $table->datetime('date');
            $table->string('status', 100);
            $table->integer('competitionId')->nullable();
            $table->integer('homeGoals')->default(0);
            $table->integer('awayGoals')->default(0);
            $table->integer('extraTimeHomeGoals')->default(0);
            $table->integer('extraTimeAwayGoals')->default(0);
            $table->integer('penaltiesHome')->default(0);
            $table->integer('penaltiesAway')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('teams');
        Schema::dropIfExists('fixtures');
    }
}
