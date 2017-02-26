<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id')->unique();
            $table->primary('id');
            $table->string('fb_id');
            $table->integer('date');
        });
        
        Schema::table('teams', function (Blueprint $table) {
            $table->integer('id')->unique()->primary();
        });
        
        Schema::table('fixtures', function (Blueprint $table) {
            $table->integer('id')->unique()->primary();
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        
        Schema::table('teams', function (Blueprint $table) {
            $table->integer('id');
        });
        
        Schema::table('fixtures', function (Blueprint $table) {
            $table->integer('id');
        });
    }
}
