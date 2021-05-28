<?php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use App\User;
use App\Models\Event;
use App\Models\Org;
use App\Models\Membertype;
use App\Models\Member;
use App\Models\Affiliate;
use App\Classes\LoadAircraft;

class DatabaseSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		$faker = Faker\Factory::create();


		$env = getenv('APP_ENV');
		Model::unguard();
		DB::statement('SET FOREIGN_KEY_CHECKS=0;');
		
		// User::truncate(); // don't remove the default root user
		Member::truncate();
		Affiliate::truncate();


		// local enviroment only ------------------
		if ($env=='local')
		{
			factory(User::Class, 20)->create();
		}

		// all enviroments including production ------------------
		//$aircraftLoader = new LoadAircraft();
		//$aircraftLoader->load_db_from_caa();
		//$aircraftLoader->import_caa_db();

		$this->call(GNZMemberSeeder::class);

		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
		Model::reguard();

		// load a heap of events
		factory(Event::class, 500)->create();


		$password = Hash::make('pass');

		User::create([
			'first_name' => 'Admin',
			'email' => 'admin@pear.co.nz',
			'password' => $password,
			'activated' => 1
		]);

		User::create([
			'first_name' => 'Admin 2',
			'email' => 'admin2@pear.co.nz',
			'password' => $password,
			'activated' => 1
		]);


		// insert a number of member types for all clubs
		$orgs = Org::where('short_name','<>','gnz')->get();
		foreach ($orgs AS $org)
		{
			$type_flying = new Membertype;
			$type_flying->name = "Flying";
			$type_flying->slug = "flying";
			$type_flying->org_id = $org->id;
			$type_flying->save();

			$type_flying = new Membertype;
			$type_flying->name = "2nd Club";
			$type_flying->slug = "2nd-club";
			$type_flying->org_id = $org->id;
			$type_flying->save();

			$type_flying = new Membertype;
			$type_flying->name = "Tow Pilot";
			$type_flying->slug = "tow-pilot";
			$type_flying->org_id = $org->id;
			$type_flying->save();

			$type_flying = new Membertype;
			$type_flying->name = "Associate";
			$type_flying->slug = "Associate";
			$type_flying->org_id = $org->id;
			$type_flying->save();

			$type_visiting = new Membertype;
			$type_visiting->name = "Visiting Pilot";
			$type_visiting->slug = "visiting-pilot";
			$type_visiting->org_id = $org->id;
			$type_visiting->save();

			$type_youth = new Membertype;
			$type_youth->name = "Youth";
			$type_youth->slug = "youth";
			$type_youth->org_id = $org->id; 
			$type_youth->save();
		}

		// insert a number of member types for all clubs
		$members = Member::where('gnz_membertype_id','<>',1)->get();
		foreach ($members AS $member)
		{
			// might belong to more than one club
			for ($i=0; $i<rand(1,3); $i++)
			{
				// create a affiliate for it
				$affiliate = new Affiliate;
				$affiliate->member_id = $member->id;
				$affiliate->org_id = $orgs[rand(0,count($orgs)-6)]->id;
				$affiliate->join_date = $faker->dateTimeBetween($startDate = '-10 years');
				$affiliate->end_date = $faker->optional(0.7)->dateTimeBetween($affiliate->start_date, $startDate = '-5 years');
				if ($affiliate->end_date!=null) $affiliate->resigned=true;
				$affiliate->save();
			}
			
		}


		// assign the created members to clubs
		

	}
}
