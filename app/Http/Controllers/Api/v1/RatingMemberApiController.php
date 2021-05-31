<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use Gate;
use Auth;
use DB;
use DateTime;
use App\Models\Member;
use App\Models\Rating;
use App\Models\Upload;
use App\Models\Org;
use App\User;
use App\Models\RatingMember;
use Carbon\Carbon;
use App\Classes\GNZLogger;

class RatingMemberApiController extends ApiController
{
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request, $member_id)
	{

		// check the member exists first
		if (!$member = Member::where('id', $member_id)->first())
		{
			return $this->error('Member not found');
		}

		// only club members (and thus club admins or admins), awards officer or membership viewers can view ratings
		if(!(Gate::check('gnz-member') || 
			Gate::check('edit-awards') || 
			Gate::check('membership-view')))
		{
			return $this->denied();
		}
		
		$ratingQuery = DB::table('rating_member')
			->leftJoin('gnz_member AS authorising_member', 'authorising_member_id', '=', 'authorising_member.id')
			->leftJoin('ratings', 'rating_id', '=', 'ratings.id')
			->leftJoin('users', 'granted_by_user_id', '=', 'users.id')
			->select(
				'authorising_member.first_name AS auth_firstname', 
				'authorising_member.last_name AS auth_lastname',
				'authorising_member.nzga_number',
				'users.first_name',
				'users.last_name',
				'ratings.*',
				'rating_member.*'
			)->where('member_id', $member->id)->orderBy('rating_member.id', 'DESC');

		if ($ratings = $ratingQuery->get())
		{
			return $this->success($ratings);
		}
		return $this->error(); 
	}



	/**
	 * Get a single rating.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function get(Request $request, $member_id, $rating_member_id)
	{

		$member = Member::findOrFail($member_id);

		// refetch the rating now we can check if we are a club admin
		$with_list[] = 'rating';
		$with_list[] = 'member';
		$with_list[] = 'uploads';

		$rating_member = RatingMember::where('id', $rating_member_id)
			->with($with_list)
			->first();

		// only club admins, awards officer can view ratings including medical documents
		if(!(Gate::check('club-admin', $member->affiliates) || 
			Gate::check('edit-awards')))
		{
			return $this->denied();
		}

		if ($auth_member = Member::where('id', $rating_member->authorising_member_id)->first())
		{
			$rating_member->auth_firstname = $auth_member->first_name;
			$rating_member->auth_lastname = $auth_member->last_name;
			$rating_member->nzga_number = $auth_member->nzga_number;
		}

		if ($added_user = User::where('id', $rating_member->granted_by_user_id)->first())
		{
			$rating_member->added_firstname = $added_user->first_name;
			$rating_member->added_lastname = $added_user->last_name;
		}

		if (!$rating_member)
		{
			return $this->error();
		}

		return $this->success($rating_member);
	}



	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		$org = $request->get('_ORG');

		$user =  Auth::user();

		if (!$request->input('rating_id')) return $this->error("rating_id is required");
		if (!$request->input('member_id')) return $this->error("member_id is required");
		if (!$request->input('awarded')) return $this->error("awarded date is required");

		// handle uploading the files
		$path = $org->folder;

		// fetch the rating
		if (!$rating = Rating::where('id', $request->input('rating_id'))->first())
		{
			return $this->error('Rating not found');
		}

		// check the member exists first
		if (!$member = Member::where('id', $request->input('member_id'))->first())
		{
			return $this->error('Member not found');
		}


		// only club admins, awards officer can edit ratings including medical documents
		if(!(Gate::check('club-admin', $member->affiliates) || 
			Gate::check('edit-awards')))
		{
			return $this->denied();
		}

		// check for awards officer only awards
		if (!Gate::check('edit-awards'))
		{
			switch ($rating->name)
			{
				case 'Cross Country Pilot (XCP)':
				case 'QGP':
					return $this->denied('Only the awards officer can give this rating');
					break;
			}
		}

		$ratingMember = new RatingMember;
		$ratingMember->expires = null;
		$ratingMember->revoked_by = null;
		$ratingMember->authorising_member_id = null;
		$ratingMember->number = null;

		// calculate expires date from months given if given
		if ($request->input('expires')) {
			if (!is_numeric($request->input('expires'))) {
				$ratingMember->expires=null;
			}
			else
			{
				$expires_date = new Carbon($request->input('awarded'));
				//$expires_date = DateTime::createFromFormat('Y-m-d', $request->input('awarded'));
				$expires_date->addMonths($request->input('expires'));
				//$expires_date->modify('+' . $request->input('expires') . ' month');
				$ratingMember->expires = $expires_date->toDateString();
			}
		}

		$awarded = new Carbon($request->input('awarded'));

		if ($request->has('ratingNumber'))
		{
			$ratingMember->number = $request->input('ratingNumber');
		}

		$ratingMember->rating_id=$request->input('rating_id');
		$ratingMember->member_id=$request->input('member_id');
		$ratingMember->awarded= $awarded->toDateString();
		$ratingMember->notes=$request->input('notes', '');
		$ratingMember->authorising_member_id=$request->input('authorising_member_id');
		$ratingMember->granted_by_user_id = $user->id;


		// save the item if all OK!
		if ($ratingMember->save())
		{
			$gnz_logger = new GNZLogger();
			$gnz_logger->log($member, 'Rating Created', $rating->name, '', $ratingMember->badge_number);

			$this->upload_files($request, $ratingMember, $org);
			return $this->success($ratingMember);
		}
		return $this->error('Something went wrong sorry');
	}


	/**
	 * Upload files for a memberRating
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function upload(Request $request, $rating_member_id)
	{
		$org = $request->get('_ORG');
		$ratingMember = RatingMember::findOrFail($rating_member_id);

		$this->upload_files($request, $ratingMember, $org);
	}


	public function upload_files($request, $ratingMember, $org)
	{
		// get rating details
		$member = Member::findOrFail($ratingMember->member_id);
		$rating = Rating::findOrFail($ratingMember->rating_id);
		$user =  Auth::user();

		// process any files that were uploaded
		foreach ($request->allFiles('files') AS $files)
		{
			$counter = 0;
			foreach ($files as $file)
			{
				$upload = new Upload();
				$upload->user_id = $user->id; // the user that uploaded the file, not the pilot
				$upload->org_id = $org->id;
				// save into the DB so we can get the ID
				$upload->save();


				$filename = simple_string(strtolower($member->last_name)) . '-' . 
							simple_string(strtolower($rating->name)) . '-' .
							$ratingMember->id . '-' . 
							$upload->id . '.' . 
							$file->getClientOriginalExtension();

				// save the file
				$path =  $file->storeAs($org->folder . 'ratings', $filename);

				// put details into database
				$upload->filename = $filename;
				$upload->folder = $org->files_path . 'ratings';
				$upload->slug = simple_string(strtolower($filename));
				$upload->type = $file->getClientOriginalExtension();
				$upload->uploadable()->associate($ratingMember);
				$upload->save();

				$gnz_logger = new GNZLogger();
				$gnz_logger->log($member, 'Rating File Uploaded', $rating->name, '', $upload->filename);

				$counter++;
			}
			
		}
	}







	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id)
	{
		
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $member_id - keep in mind this might be faked, so don't trust it
	 * @param  int  $rating_member_id - the actual rating_member link we are deleting
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Request $request, $member_id, $rating_member_id)
	{
		$ratingMember = RatingMember::findOrFail($rating_member_id);

		// get membership details of this rating member
		$member = Member::findOrFail($ratingMember->member_id);
		$rating = Member::findOrFail($ratingMember->rating_id);

		// get the org
		if (!$org = Org::where('gnz_code', '=', $member->club)->first())
		{
			return $this->denied();
		}

		// check we are club admin for the person's org we are editing
		if (Gate::denies('club-admin', $org) && Gate::denies('edit-awards')) return $this->denied();

		if ($ratingMember->delete())
		{
			$gnz_logger = new GNZLogger();
			$gnz_logger->log($member, 'Rating Deleted', $rating->name);

			return $this->success('Rating Deleted');
		}
		return $this->error(); 
	}


	public function destroyFile(Request $request, $member_id, $rating_member_id, $upload_id)
	{
		$member = Member::findOrFail($member_id);
		$ratingMember = RatingMember::findOrFail($rating_member_id);
		$rating = Member::findOrFail($ratingMember->rating_id);

		// get the org
		if (!$org = Org::where('gnz_code', '=', $member->club)->first()) return $this->denied();

		if (Gate::denies('club-admin', $org)) return $this->denied();

		$upload = Upload::findOrFail($upload_id);
		if ($upload->delete())
		{
			$gnz_logger = new GNZLogger();
			$gnz_logger->log($member, 'Rating File Deleted', $rating->name, $upload->filename);

			return $this->success('File Deleted');
		}
		return $this->error(); 
	}




	public function ratingsReport(Request $request)
	{
		//	LEFT JOIN (SELECT awarded AS medical_awarded, member_id, expires AS medical_expires, DATE_ADD(awarded, INTERVAL 2 YEAR) AS two_year_medical_expire, DATE_ADD(awarded, INTERVAL 5 YEAR) AS five_year_medical_expire FROM rating_member AS medical WHERE rating_id>=3 AND rating_id<=7 AND medical.member_id=gnz_member.id ORDER BY medical_awarded DESC LIMIT 1) AS medical ON medical.member_id=gnz_member.id

		
		$org = Org::where('gnz_code', $request->input('org'))->first();


		$members = DB::select('SELECT 
			gnz_member.id, first_name, middle_name, last_name, nzga_number, date_of_birth
			FROM gnz_member, affiliates
			WHERE affiliates.member_id=gnz_member.id AND affiliates.org_id=:org_id AND affiliates.resigned=0
			ORDER BY last_name', ['org_id' => $org->id]);


//, bfr.bfr_expires, medical.medical_expires, bfr.bfr_awarded, medical.medical_awarded, qgp.qgp_awarded, xcp.xcp_awarded, passenger.passenger_awarded, insta.insta_awarded, instb.instb_awarded, instc.instc_awarded, instd.instd_awarded, 
//				IF(DATE_SUB(medical.medical_awarded, INTERVAL 40 YEAR) < date_of_birth, medical.five_year_medical_expire, medical.two_year_medical_expire) AS medical_passenger_expires


// LEFT JOIN (SELECT MAX(awarded) AS medical_awarded, member_id, MAX(expires) AS medical_expires, DATE_ADD(MAX(awarded), INTERVAL 2 YEAR) AS two_year_medical_expire, DATE_ADD(MAX(awarded), INTERVAL 5 YEAR) AS five_year_medical_expire FROM rating_member AS medical WHERE rating_id>=3 AND rating_id<=7  GROUP BY member_id) AS medical ON medical.member_id=gnz_member.id
// 			LEFT JOIN (SELECT MAX(expires) AS bfr_expires, member_id, MAX(awarded) AS bfr_awarded FROM rating_member AS bfr WHERE rating_id=2 AND expires IS NOT NULL GROUP BY member_id) AS bfr ON bfr.member_id=gnz_member.id
// 			LEFT JOIN (SELECT MAX(awarded) AS qgp_awarded, member_id FROM rating_member AS qgp WHERE rating_id=1 GROUP BY member_id) AS qgp ON qgp.member_id=gnz_member.id
// 			LEFT JOIN (SELECT MAX(awarded) AS xcp_awarded, member_id FROM rating_member AS xcp WHERE rating_id=35 GROUP BY member_id) AS xcp ON xcp.member_id=gnz_member.id
// 			LEFT JOIN (SELECT MAX(awarded) AS passenger_awarded, member_id FROM rating_member AS passenger WHERE rating_id=8 GROUP BY member_id) AS passenger ON passenger.member_id=gnz_member.id
// 			LEFT JOIN (SELECT MAX(awarded) AS insta_awarded, member_id FROM rating_member AS insta WHERE rating_id=9 GROUP BY member_id) AS insta ON insta.member_id=gnz_member.id
// 			LEFT JOIN (SELECT MAX(awarded) AS instb_awarded, member_id FROM rating_member AS instb WHERE rating_id=10 GROUP BY member_id) AS instb ON instb.member_id=gnz_member.id
// 			LEFT JOIN (SELECT MAX(awarded) AS instc_awarded, member_id FROM rating_member AS instc WHERE rating_id=11 GROUP BY member_id) AS instc ON instc.member_id=gnz_member.id
// 			LEFT JOIN (SELECT MAX(awarded) AS instd_awarded, member_id FROM rating_member AS instd WHERE rating_id=12 GROUP BY member_id) AS instd ON instd.member_id=gnz_member.id


		if ($members)
		{
			// get the extra data for each row
			foreach ($members AS $member)
			{
				// get the medical details
				$fields = DB::select('
					SELECT awarded, member_id, expires, DATE_ADD(awarded, INTERVAL 2 YEAR) AS two_year_medical_expires, DATE_ADD(awarded, INTERVAL 5 YEAR) AS five_year_medical_expires, 
					IF(DATE_SUB(awarded, INTERVAL 40 YEAR) < :dob, DATE_ADD(awarded, INTERVAL 5 YEAR), DATE_ADD(awarded, INTERVAL 2 YEAR)) AS medical_passenger_expires 
					FROM rating_member AS medical 
					WHERE rating_id>=3 AND rating_id<=7 AND medical.member_id=:member_id 
					ORDER BY awarded DESC
					LIMIT 1', ['member_id' => $member->id, 'dob' => $member->date_of_birth]);

				if (count($fields)>0)
				{
					$member->medical_passenger_expires = $fields[0]->medical_passenger_expires;
					$member->medical_awarded = $fields[0]->awarded;
					$member->medical_expires = $fields[0]->expires;
					$member->two_year_medical_expires = $fields[0]->two_year_medical_expires;
					$member->five_year_medical_expires = $fields[0]->five_year_medical_expires;
				}
				else
				{
					$member->medical_passenger_expires = null;
					$member->medical_awarded = null;
					$member->medical_expires = null;
					$member->two_year_medical_expire = null;
					$member->five_year_medical_expire = null;
				}


				// simmple ratings to check
				$ratings = Array();
				$ratings[] = Array('name'=>'bfr', 'id'=>2);
				$ratings[] = Array('name'=>'xcp', 'id'=>35);
				$ratings[] = Array('name'=>'qgp', 'id'=>1);
				$ratings[] = Array('name'=>'passenger', 'id'=>8);
				$ratings[] = Array('name'=>'insta', 'id'=>9);
				$ratings[] = Array('name'=>'instb', 'id'=>10);
				$ratings[] = Array('name'=>'instc', 'id'=>11);
				$ratings[] = Array('name'=>'instd', 'id'=>12);

				foreach ($ratings AS $rating)
				{
					$fields = DB::select('
						SELECT awarded, expires
						FROM rating_member 
						WHERE rating_id='.$rating['id'].'  AND rating_member.member_id=:member_id 
						ORDER BY awarded DESC
						LIMIT 1', ['member_id' => $member->id]);

					if (count($fields)>0) 
					{
						$member->{$rating['name'] . '_awarded'} = $fields[0]->awarded;
						$member->{$rating['name'] . '_expires'} = $fields[0]->expires;
					}
					else
					{
						$member->{$rating['name'] . '_awarded'} = null;
						$member->{$rating['name'] . '_expires'} = null;
					}
				}


			}

			return $this->success($members);
		}
		return $this->error(); 
	}




	public function lastRatingNumber(Request $request, $rating_id)
	{
		// get the given rating
		if (!$rating = Rating::find($rating_id)) return $this->not_found('Rating Not Found');

		// check it should be numbered
		if (!$rating->numbered) $this->error('This rating is not numbered');

		// get the max (should be last) known number
		if ($number = RatingMember::where('rating_id', $rating_id)->max('number'))
		{
			return $this->success($number);
		}
		
		return $this->success(0); 
	}





}
