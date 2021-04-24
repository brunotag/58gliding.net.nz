<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Membertype;
use App\Models\Position;
use App\Models\Org;
use App\Models\Affiliate;

class Membersv2 extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		// remove the ENUM that doesn't work with Laravel very well
		DB::statement("SET SQL_MODE='ALLOW_INVALID_DATES'");
		DB::statement('ALTER TABLE gnz_member DROP COLUMN access_level');
		DB::statement('ALTER TABLE gnz_member MODIFY gender VARCHAR(1)');

		// upgrade the members table
		Schema::table('gnz_member', function (Blueprint $table) {
			$table->string('password', 40)->nullable()->change();
			$table->string('salt', 40)->nullable()->change();
			$table->string('email', 64)->nullable()->change();
			$table->string('first_name', 64)->nullable()->change();
			$table->string('last_name', 64)->nullable()->change();
			$table->boolean('needs_gnz_approval')->nullable();
			$table->integer('gnz_membertype_id')->nullable();
		});





		// Add ability to identify defunct organisations
		
		Schema::table('orgs', function (Blueprint $table) {
			$table->boolean('active')->default(true);
			$table->string('twitter')->nullable();
			$table->string('facebook')->nullable();
			$table->integer('waypoint_id')->nullable();
			$table->text('description')->nullable();
		});

		// insert defunct orgs
		if (!Org::where('gnz_code','gsl')->exists())
		{
			$neworg = new Org;
			$neworg->name = "Gliding South";
			$neworg->short_name = "South";
			$neworg->slug = "south";
			$neworg->website = "";
			$neworg->gnz_code = "gsl";
			$neworg->type = "club";
			$neworg->active = false;
			$neworg->save();
		}

		if (!Org::where('gnz_code','whg')->exists())
		{
			$neworg = new Org;
			$neworg->name = "Whangarei";
			$neworg->short_name = "Whangarei";
			$neworg->slug = "whangarei";
			$neworg->website = "";
			$neworg->gnz_code = "whg";
			$neworg->type = "club";
			$neworg->active = false;
			$neworg->save();
		}
		if (!Org::where('gnz_code','hac')->exists())
		{
			$neworg = new Org;
			$neworg->name = "Hauraki Aero Club";
			$neworg->short_name = "Hauraki";
			$neworg->slug = "hauraki";
			$neworg->website = "";
			$neworg->gnz_code = "hac";
			$neworg->type = "club";
			$neworg->active = false;
			$neworg->save();
		}
		if (!Org::where('gnz_code','wav')->exists())
		{
			$neworg = new Org;
			$neworg->name = "Wigram Aviation Sports";
			$neworg->short_name = "Wigram";
			$neworg->slug = "wigram";
			$neworg->website = "";
			$neworg->gnz_code = "wav";
			$neworg->type = "club";
			$neworg->active = false;
			$neworg->save();
		}
		if (!Org::where('gnz_code','not')->exists())
		{
			$neworg = new Org;
			$neworg->name = "North Otago";
			$neworg->short_name = "North Otago";
			$neworg->slug = "not";
			$neworg->website = "";
			$neworg->gnz_code = "not";
			$neworg->type = "club";
			$neworg->active = false;
			$neworg->save();
		}
		if (!Org::where('gnz_code','otg')->exists())
		{
			$neworg = new Org;
			$neworg->name = "Otago Gliding Club";
			$neworg->short_name = "Otago Gliding Club";
			$neworg->slug = "otg";
			$neworg->website = "";
			$neworg->gnz_code = "otg";
			$neworg->type = "club";
			$neworg->active = false;
			$neworg->save();
		}
		if (!Org::where('gnz_code','clv')->exists())
		{
			$neworg = new Org;
			$neworg->name = "Clutha Valley";
			$neworg->short_name = "Clutha Valley";
			$neworg->slug = "clutha";
			$neworg->website = "";
			$neworg->gnz_code = "clv";
			$neworg->type = "club";
			$neworg->active = false;
			$neworg->save();
		}
		if (!Org::where('gnz_code','ssg')->exists())
		{
			$neworg = new Org;
			$neworg->name = "Southern Soaring";
			$neworg->short_name = "Southern Soaring";
			$neworg->slug = "ssg";
			$neworg->website = "";
			$neworg->gnz_code = "ssg";
			$neworg->type = "club";
			$neworg->active = false;
			$neworg->save();
		}
		if (!Org::where('gnz_code','ras')->exists())
		{
			$neworg = new Org;
			$neworg->name = "Rangiora Advanced Soaring";
			$neworg->short_name = "Rangiora Advanced Soaring";
			$neworg->slug = "ras";
			$neworg->website = "";
			$neworg->gnz_code = "ras";
			$neworg->type = "club";
			$neworg->active = false;
			$neworg->save();
		}
		if (!Org::where('gnz_code','alp')->exists())
		{
			$neworg = new Org;
			$neworg->name = "Alpine Soaring";
			$neworg->short_name = "Alpine Soaring";
			$neworg->slug = "alp";
			$neworg->website = "";
			$neworg->gnz_code = "alp";
			$neworg->type = "club";
			$neworg->active = false;
			$neworg->save();
		}
		if (!Org::where('gnz_code','rga')->exists())
		{
			$neworg = new Org;
			$neworg->name = "Rangiora";
			$neworg->short_name = "Rangiora";
			$neworg->slug = "rangiora";
			$neworg->website = "";
			$neworg->gnz_code = "rga";
			$neworg->type = "club";
			$neworg->active = false;
			$neworg->save();
		}
		if (!Org::where('gnz_code','rpu')->exists())
		{
			$neworg = new Org;
			$neworg->name = "Ruapehu";
			$neworg->short_name = "Ruapehu";
			$neworg->slug = "ruapehu";
			$neworg->website = "";
			$neworg->gnz_code = "rpu";
			$neworg->type = "club";
			$neworg->active = false;
			$neworg->save();
		}
		if (!Org::where('gnz_code','epb')->exists())
		{
			$neworg = new Org;
			$neworg->name = "Eastern Bay of Plenty";
			$neworg->short_name = "Eastern Bay of Plenty";
			$neworg->slug = "epb";
			$neworg->website = "";
			$neworg->gnz_code = "epb";
			$neworg->type = "club";
			$neworg->active = false;
			$neworg->save();
		}
		if (!Org::where('gnz_code','sky')->exists())
		{
			$neworg = new Org;
			$neworg->name = "Sky Sailing";
			$neworg->short_name = "Sky Sailing";
			$neworg->slug = "sky";
			$neworg->website = "";
			$neworg->gnz_code = "sky";
			$neworg->type = "club";
			$neworg->active = false;
			$neworg->save();
		}


		// Table to link a member with an organisation, and track membership history
		if (!Schema::hasTable('affiliates')) {
			Schema::create('affiliates', function (Blueprint $table) {
				$table->increments('id');
				$table->integer('member_id');
				$table->integer('org_id');
				$table->integer('membertype_id')->nullable();
				$table->date('join_date')->nullable();
				$table->date('end_date')->nullable();
				$table->text('resigned_comment')->nullable();
				$table->boolean('resigned')->default(false);
				$table->timestamps();
				$table->index('member_id', 'member_index');
			});
		}



		// membertype types table
		if (!Schema::hasTable('membertypes')) {
			Schema::create('membertypes', function (Blueprint $table) {
				$table->increments('id');
				$table->string('name');
				$table->string('slug');
				$table->integer('org_id')->nullable();
				$table->decimal('annual_fee')->nullable();
				$table->timestamps();
			});

			// get the GNZ org and setup the membertype types
			if ($gnz = Org::where('slug', 'gnz')->first())
			{
				$type_resigned = new Membertype;
				$type_resigned->name = "Resigned";
				$type_resigned->slug = "resigned";
				$type_resigned->org_id = $gnz->id;
				$type_resigned->save();

				$type_flying = new Membertype;
				$type_flying->name = "Flying";
				$type_flying->slug = "flying";
				$type_flying->org_id = $gnz->id;
				$type_flying->save();

				$type_com = new Membertype;
				$type_com->name = "Communication only";
				$type_com->slug = "communication-only";
				$type_com->org_id = $gnz->id;
				$type_com->save();

				$type_visiting = new Membertype;
				$type_visiting->name = "Visiting Pilot";
				$type_visiting->slug = "visiting-pilot";
				$type_visiting->org_id = $gnz->id;
				$type_visiting->save();

				$type_youth = new Membertype;
				$type_youth->name = "Youth";
				$type_youth->slug = "youth";
				$type_youth->org_id = $gnz->id; 
				$type_youth->save();
			}
		}
 

		// Link member and membertype
		if (!Schema::hasTable('member_membertype')) {
			Schema::create('member_membertype', function (Blueprint $table) {
				$table->increments('id');
				$table->integer('member_id');
				$table->integer('membertype_id');
				$table->timestamps();
			});
		}

		// Member Club Positions e.g. Secratary, CFI etc
		if (!Schema::hasTable('positions')) {
			Schema::create('positions', function (Blueprint $table) {
				$table->increments('id');
				$table->string('name');
				$table->string('abbreviation');
				$table->string('slug');
				$table->timestamps();
			});

			$rating = new Position;
			$rating->name = "President";
			$rating->abbreviation = "President";
			$rating->slug = "president";
			$rating->save();

			$rating = new Position;
			$rating->name = "Vice President";
			$rating->abbreviation = "Vice President";
			$rating->slug = "vice-president";
			$rating->save();

			// insert basic positions
			$rating = new Position;
			$rating->name = "Chief Flying Instructor";
			$rating->abbreviation = "CFI";
			$rating->slug = "cfi";
			$rating->save();

			$rating = new Position;
			$rating->name = "Chief Tow Pilot";
			$rating->abbreviation = "CTP";
			$rating->slug = "ctp";
			$rating->save();

			$rating = new Position;
			$rating->name = "Secratary";
			$rating->abbreviation = "secratary";
			$rating->slug = "secratary";
			$rating->save();

			$rating = new Position;
			$rating->name = "Treasurer";
			$rating->abbreviation = "Treasurer";
			$rating->slug = "treasurer";
			$rating->save();

			$rating = new Position;
			$rating->name = "Awards Officer";
			$rating->abbreviation = "Awards Officer";
			$rating->slug = "awards-officer";
			$rating->save();

			$rating = new Position;
			$rating->name = "Committee Member";
			$rating->abbreviation = "Committee";
			$rating->slug = "committee";
			$rating->save();
		}

		// join member positions
		if (!Schema::hasTable('member_position')) {
			Schema::create('member_position', function (Blueprint $table) {
				$table->increments('id');
				$table->integer('member_id');
				$table->integer('position_id');
				$table->timestamps();
			});
		}



		// do the migration of the data
		// 
		// get the list or orgs
		$orgs = DB::table('orgs')->get();
		foreach ($orgs AS $key=>$org)
		{
			$orgs_gnz_code[$org->gnz_code] = $org;
		}

		// get all members, and store club details
		$members = DB::table('gnz_member')->get();
		foreach ($members AS $member)
		{
			// set the correct GNZ member type
			switch ($member->membership_type)
			{
				case 'Resigned':
					$member->gnz_membertype_id = $type_resigned->id;
					break;
				case 'Flying':
					$member->gnz_membertype_id = $type_flying->id;
					break;
				case 'Mag Only':
					$member->gnz_membertype_id = $type_com->id;
					break;
				case 'VFP 3 Mth':
					$member->gnz_membertype_id = $type_visiting->id;
					break;
				case 'Junior':
					$member->gnz_membertype_id = $type_youth->id;
					break;
				default:
					$member->gnz_membertype_id = null;
					break;
			}

			// update the member. Not using eloquent otherwise it runs out of memory and couldn't be 
			// bothered chunking
			DB::table('gnz_member')->where('id', $member->id)->update(['gnz_membertype_id' => $member->gnz_membertype_id, 'membership_type'=>'']);

			// handle previous clubs
			if ($member->previous_clubs!=null && $member->previous_clubs!='')
			{
				$prev_clubs = explode(' ', $member->previous_clubs);
				foreach ($prev_clubs AS $prev_club)
				{

					// convert other names to the correct code
					switch($prev_club)
					{
						case 'Tauranga Gliding Club': 
						case 'Tauranga': 
							$prev_club='TGA'; break;
						case 'Auckland Gliding Club':
						case 'Auckland':
							$prev_club='AKL'; break;
						case 'Wellington Gliding Club':
						case 'Wellington':
							$prev_club='WLN'; break;
						case 'Piako Gliding Club':
						case 'Piako':
						case 'PkoPKO':
							$prev_club='PKO'; break;
						case 'South Canterbury Gliding Club':
							$prev_club='SCY'; break;
						case 'Nelson Lakes Gliding Club':
							$prev_club='NLN'; break;
						case 'Canterbury Gliding':
							$prev_club='CTY'; break;
						case 'Taupo Gliding Club':
						case 'TpoTPO ':
							$prev_club='TPO'; break;
						case 'AklAKL  ':
							$prev_club='AKL'; break;
						case 'Omarama Gliding Club  ':
							$prev_club='OGC'; break;
						case 'GSLOTG':
							$prev_club='GSL'; break;
						case 'Waipukurau Gliding Club':
						case 'Waipukurau Gliding ClubHBY':
							$prev_club='GSL'; break;
						case 'Marlborough Gliding Club':
							$prev_club='MLB'; break;
						case 'South Canterbury Gliding Clu':
							$prev_club='SCY'; break;
						case 'GomGOM ':
							$prev_club='GOM'; break;
						case 'Gliding Manawatu':
							$prev_club='WGM'; break;
						case 'Gliding Wairarapa':
							$prev_club='GWR'; break;
						case 'Hawkes Bay Gliding Club':
						case 'Waipukurau Gliding Club/ Hawkes Bay Gliding Club':
							$prev_club='HBY'; break;
						case 'Auckland Aviation Sports Club':
							$prev_club='AAV'; break;
						case 'Norfolk Aviation Sports Club':
							$prev_club='AAV'; break;
					}


					if (isset($orgs_gnz_code[$prev_club]))
					{
						$affiliate = new Affiliate();
						$affiliate->org_id = $orgs_gnz_code[$prev_club]->id;
						$affiliate->member_id = $member->id;
						$affiliate->resigned = true; // previous clubs are always resigned?

						// figure out if the resigned date applies
						if ($member->membership_type!=='resigned' && sizeof($prev_clubs)==1)
						{
							// if there are multiple previous clubs, then we have no way of knowing which one had the expiry date. So only do this if there is only 1 previous club.
							$affiliate->end_date = $member->resigned;
						}

						$affiliate->save();
					}
				}
			}

			// handle current club
			if ($member->club!=null && $member->club!='')
			{
				if (isset($orgs_gnz_code[$member->club]))
				{
					$affiliate = new Affiliate();
					$affiliate->org_id = $orgs_gnz_code[$member->club]->id;
					$affiliate->member_id = $member->id;
					$affiliate->join_date = $member->date_joined;
					$affiliate->end_date = $member->resigned;
					$affiliate->resigned_comment = $member->resigned_comment;

					if ($member->membership_type=='Resigned') $affiliate->resigned=true;

					$affiliate->save();
				}
			}
		}

		DB::statement('ALTER TABLE gnz_member DROP COLUMN membership_type');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("SET SQL_MODE='ALLOW_INVALID_DATES'");
		DB::statement("ALTER TABLE gnz_member ADD COLUMN access_level VARCHAR(16)");

		Schema::drop('affiliates');
		Schema::drop('membertypes');
		Schema::drop('member_membertype');
		Schema::drop('positions');
		Schema::drop('member_position');
		
		Org::where('gnz_code','gsl')->delete();
		Org::where('gnz_code','whg')->delete();
		Org::where('gnz_code','hac')->delete();
		Org::where('gnz_code','wav')->delete();
		Org::where('gnz_code','not')->delete();
		Org::where('gnz_code','otg')->delete();
		Org::where('gnz_code','clv')->delete();
		Org::where('gnz_code','ssg')->delete();
		Org::where('gnz_code','ras')->delete();
		Org::where('gnz_code','alp')->delete();
		Org::where('gnz_code','rga')->delete();
		Org::where('gnz_code','rpu')->delete();
		Org::where('gnz_code','epb')->delete();
		Org::where('gnz_code','sky')->delete();


		Schema::table('orgs', function (Blueprint $table) {
			$table->dropColumn('active');
			$table->dropColumn('twitter');
			$table->dropColumn('facebook');
			$table->dropColumn('waypoint_id');
			$table->dropColumn('description');
		});


		Schema::table('gnz_member', function (Blueprint $table) {
			$table->dropColumn('needs_gnz_approval');
			$table->dropColumn('gnz_membertype_id');
		});

	}
}
