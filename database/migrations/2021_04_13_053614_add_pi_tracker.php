<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPiTracker extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//
		Schema::table('aircraft', function ($table) {
			$table->string('pi')->nullable()->default(null);
		});

		/** Used to track when a device was last seen, so we can 
			discover it when connecting it to an aircraft */
		if (!Schema::connection('ogn')->hasTable('devices')) {
			Schema::connection('ogn')->create('devices', function(Blueprint $table) {
				$table->integer('id', true);
				$table->string('device_id')->nullable();
				$table->datetime('last_turned_on')->nullable();
				$table->timestamps();
				$table->index('device_id');
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		//
		Schema::table('aircraft', function ($table) {
			$table->dropColumn('pi');
		});

		if (!Schema::connection('ogn')->hasTable('devices')) {
			Schema::cconnection('ogn')->drop('devices');
		}
	}
}
