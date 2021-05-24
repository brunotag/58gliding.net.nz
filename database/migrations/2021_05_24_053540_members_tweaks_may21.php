<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MembersTweaksMay21 extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//
		Schema::table('gnz_member', function(Blueprint $table) {
			$table->date('first_date_joined')->nullable();
		});

		\DB::statement('UPDATE gnz_member SET first_date_joined = date_joined');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('gnz_member', function(Blueprint $table) {
			$table->dropColumn('first_date_joined');
		});
	}
}
