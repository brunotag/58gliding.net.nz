<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PiTrackerUpgrade extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        /** Keep track of which bluetooth LE device is linked to which member */
        if (!Schema::hasTable('tiles')) {
            Schema::create('tiles', function(Blueprint $table) {
                $table->integer('id', true);
                $table->string('hex')->nullable();
                $table->integer('member_id')->nullable();
                $table->string('type')->nullable();
                $table->string('note')->nullable();
                $table->index(['hex']);
                $table->datetime('last_seen')->nullable();
                $table->integer('last_strength')->nullable();
                $table->integer('last_aircraft_id')->nullable();
                $table->string('last_device_id')->nullable();
             $table->timestamps();
            });
        }

        Schema::table('aviators', function ($table) {
            $table->integer('tile_id')->nullable();
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('tiles')) {
            Schema::drop('tiles');
        }
        if (Schema::hasTable('aviators')) {
            Schema::table('aviators', function (Blueprint $table) {
                $table->dropColumn('tile_id');
            });
        }
    }
}
