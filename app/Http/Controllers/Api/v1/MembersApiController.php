<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;

use App\Classes\MemberUtilities;
use App\Classes\Settings;
use App\Http\Requests;
use App\Http\Controllers\Api\ApiController;
use App\Models\Member;
use App\Models\Membertype;
use App\Models\MemberChangeLog;
use App\Models\Org;
use App\Models\Affiliate;
use App\Exports\MembersExport;
use App\Mail\RequestGnzApproval;
use App\Mail\ResignGnzMember;
use App\Mail\NotifyGnzMemberTypeChange;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use DB;
use DateTime;
use Excel;
use Auth;
use URL;
use Mailgun;
use Mail;
use Schema;
use Gate;

class MembersApiController extends ApiController
{

	// create a new member
	public function store(Request $request)
	{
		// check we have permission to create a new member
		if (Gate::denies('club-admin')) {
			return $this->denied();
		}

		// get current org
		$org = \Request::get('_ORG');

		$this->validate($request, [
			'first_name' => 'required|max:64',
			'last_name' => 'required|max:64'
		]);

		$member = new Member();
		$member->first_name = $request->input('first_name');
		$member->last_name = $request->input('last_name');
		$member->password = '';
		$member->salt = '';
		$member->non_member = 1;
		$member->middle_name = '';
		$member->email = '';
		$member->club = $org->gnz_code;
		$member->needs_gnz_approval = true;
		$member->gnz_membertype_id = $request->input('gnz_membertype_id', null);
		$member->date_joined = $request->input('date_joined', Carbon::now(new CarbonTimeZone('Pacific/Auckland')));
		$member->first_date_joined = $request->input('date_joined', Carbon::now(new CarbonTimeZone('Pacific/Auckland')));
		$member->created = Carbon::now(new CarbonTimeZone('Pacific/Auckland'));

		if ( $request->input('submit_to_gnz') ) {
			$member->pending_approval = 1;
		} else {
			$member->pending_approval = 0;
		}

		$this->log_new_member($member);

		if ($member->save())
		{
			// create the affiliate link
			if (isset($org->id))
			{
				$affiliate = new Affiliate();
				$affiliate->member_id = $member->id;
				$affiliate->org_id = $org->id;
				$affiliate->join_date = $request->input('date_joined', Carbon::now(new CarbonTimeZone('Pacific/Auckland')));
				$affiliate->membertype_id = $request->input('membertype_id', null);
				$affiliate->save();
			}

			return $this->success($member);
		}

		return $this->error('Member not created');
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		if ($member = Member::with('affiliates.org')->find($id))
		{
			$member_utilities = new MemberUtilities();
			$member_utilities->filter_view_result($member);
			return $this->success($member);
		}
		return $this->not_found();
	}




	/**
	 * Set a GNZ number for a user.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function setGnzNumber($id)
	{
		// check we have permission to request approval
		if (Gate::denies('admin')) {
			return $this->denied();
		}

		// find them member and set their pending approval status, and send an email notification
		if ($member = Member::with('affiliates.org')->find($id))
		{
			// find the next GNZ number in the database
			$largest_gnz_number = Member::max('nzga_number');

			$old_gnz_number = $member->nzga_number;
			$member->nzga_number = $largest_gnz_number + 1;
			$member->save();

			// ($member, $action, $field, $oldval, $newval)
			$this->log_member_change($member, 'Set GNZ Number', 'membership_type', $old_gnz_number, $member->nzga_number);

			// TODO send Email notification here
			return $this->success($member);
		}
		return $this->not_found();
	}




	/**
	 * Resign a GNZ member
	 * Any club admin can do this, but the GNZ admin will be notified.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function resignGnz(Request $request, $id)
	{
		// check we have permission to request approval
		if (Gate::denies('any-club-admin')) {
			return $this->denied();
		}

		// find them member and set their pending approval status, and send an email notification
		if ($member = Member::with('affiliates.org')->find($id))
		{
			$resigned_type = null;
			if ($gnz_org = Org::where('short_name', '=', 'GNZ')->first())
			{
				$resigned_type = Membertype::where('org_id', '=', $gnz_org->id)->where('slug', '=', 'resigned')->first();
			}

			$old_member_type = $member->gnz_membertype_id;
			if (isset($resigned_type->id)) $member->gnz_membertype_id = $resigned_type->id;
			$member->pending_approval = false;

			// get the resign date
			$old_resign_date = $member->resigned;
			$member->resigned = $request->get('resign_date');

			// get the resign reason
			$member->resigned_comment = $request->get('resigned_comment');
			$member->needs_gnz_approval = true;

			if ($member->save())
			{
				// ($member, $action, $field, $oldval, $newval)
				$this->log_member_change($member, 'Resign Member', 'membership_type', $old_member_type, $member->membership_type);
				$this->log_member_change($member, 'Resign Member', 'resigned', $old_resign_date, $member->resigned);

				$settings = new Settings();
				if ($gnz_org = Org::where('short_name', '=', 'GNZ')->first())
				{
					if ($gnz_email = $settings->get('email_new_member_to', $gnz_org))
					{
						Mail::to($gnz_email)->send(new ResignGnzMember($member));
					}
				}

				return $this->success($member);
			}

		}
		return $this->not_found();
	}


	/**
	 * Given a GNZ user ID, find all other users with the same birthdate.
	 * This is admin only feature, so to not give away birthday private information
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function findDuplicates(Request $request, $id)
	{
		// check we have permission to request approval
		if (Gate::denies('admin')) {
			return $this->denied();
		}

		// find them member and set their pending approval status, and send an email notification
		if ($member = Member::find($id))
		{
			$potential_same_users = Member::where('date_of_birth', '=', $member->date_of_birth)
				->where('id', '<>', $member->id)
				->get();
			if (count($potential_same_users)>=0)
			{
				return $this->success($potential_same_users);
			}
			return $this->success();
		}
		return $this->not_found();
	}




	/**
	 * Request GNZ approval for a user.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function requestApproval($id)
	{
		// check we have permission to request approval
		if (Gate::denies('any-club-admin')) {
			return $this->denied();
		}

		// find them member and set their pending approval status, and send an email notification
		if ($member = Member::with('affiliates.org')->find($id))
		{
			$old_pending_approval = $member->pending_approval;
			$member->pending_approval = true;
			$member->save();

			// ($member, $action, $field, $oldval, $newval)
			$this->log_member_change($member, 'Requested Approval', 'pending_approval', $old_pending_approval, $member->pending_approval);
			
			// get the GNZ admin email address
			$settings = new Settings();
			if ($gnz_org = Org::where('short_name', '=', 'GNZ')->first())
			{
				if ($gnz_email = $settings->get('email_new_member_to', $gnz_org))
				{
					Mail::to($gnz_email)->send(new RequestGnzApproval($member));
				}
			}

			return $this->success($member);
		}
		return $this->not_found();
	}



	public function update(Request $request, $id)
	{

		// check member exists
		if (!$member = Member::find($id))
		{
			return $this->not_found();
		}

		// check we have permission to edit the member
		if (Gate::denies('edit-member', $member)) {
			return $this->denied();
		}

		// filter to only the items we're allowed to edit
		//$this->_filter_edit_results($member);

		//$form = $request->all();
		//unset($form['id']);

		// only allow updating of the correct items depending on your user access.
		if (Gate::allows('edit-self', $member) || Gate::allows('edit-member', $member))
		{
			$form['first_name'] = $request->get('first_name');
			$form['middle_name'] = $request->get('middle_name');
			$form['last_name'] = $request->get('last_name');
			$form['email'] = $request->get('email');
			$form['gender'] = $request->get('gender');
			$form['address_1'] = $request->get('address_1');
			$form['address_2'] = $request->get('address_2');
			$form['city'] = $request->get('city');
			$form['country'] = $request->get('country');
			$form['zip_post'] = $request->get('zip_post');
			$form['home_phone'] = $request->get('home_phone');
			$form['city'] = $request->get('city');
			$form['mobile_phone'] = $request->get('mobile_phone');
			$form['business_phone'] = $request->get('business_phone');
			$form['coach'] = $request->get('coach');
			$form['contest_pilot'] = $request->get('contest_pilot');
			$form['privacy'] = $request->get('privacy');
		}

		// Official awards
		if (Gate::allows('edit-awards'))
		{
			$form['observer_number'] = $request->get('observer_number');
			$form['awards'] = $request->get('awards');
			$form['qgp_number'] = $request->get('qgp_number');
			$form['date_of_qgp'] = $request->get('date_of_qgp');
			$form['silver_certificate_number'] = $request->get('silver_certificate_number');
			$form['silver_duration'] = $request->get('silver_duration');
			$form['silver_distance'] = $request->get('silver_distance');
			$form['silver_height'] = $request->get('silver_height');
			$form['gold_badge_number'] = $request->get('gold_badge_number');
			$form['gold_distance'] = $request->get('gold_distance');
			$form['gold_height'] = $request->get('gold_height');
			$form['diamond_distance_number'] = $request->get('diamond_distance_number');
			$form['diamond_height_number'] = $request->get('diamond_height_number');
			$form['diamond_goal_number'] = $request->get('diamond_goal_number');
			$form['all_3_diamonds_number'] = $request->get('all_3_diamonds_number');
			$form['flight_1000km_number'] = $request->get('flight_1000km_number');
			$form['flight_1250km_number'] = $request->get('flight_1250km_number');
			$form['flight_1500km_number'] = $request->get('flight_1500km_number');
		}


		// club administrators 
		if (Gate::allows('any-club-admin'))
		{
			$form['date_of_birth'] = $request->get('date_of_birth');
			$form['instructor'] = $request->get('instructor');
			$form['instructor_rating'] = $request->get('instructor_rating');
			$form['aero_tow'] = $request->get('aero_tow');
			$form['instructor'] = $request->get('instructor');
			$form['aero_tow'] = $request->get('aero_tow');
			$form['winch_rating'] = $request->get('winch_rating');
			$form['self_launch'] = $request->get('self_launch');
			$form['insttrain'] = $request->get('insttrain');
			$form['tow_pilot'] = $request->get('tow_pilot');
			$form['instructor_trainer'] = $request->get('instructor_trainer');
			$form['tow_pilot_instructor'] = $request->get('tow_pilot_instructor');
			$form['aero_instructor'] = $request->get('aero_instructor');
			$form['advanced_aero_instructor'] = $request->get('advanced_aero_instructor');
			$form['auto_tow'] = $request->get('auto_tow');

			// gnz things that club admins can also change
			$form['gnz_membertype_id'] = $request->get('gnz_membertype_id');
			$form['date_joined'] = $request->get('date_joined');
			$form['first_date_joined'] = $request->get('first_date_joined');
			$form['club'] = $request->get('club');

		}

		// GNZ admin only
		if (Gate::allows('admin'))
		{
			$form['nzga_number'] = $request->get('nzga_number');
			$form['comments'] = $request->get('comments');
			$form['pending_approval'] = $request->get('pending_approval');
			$form['non_member'] = $request->get('non_member');
			$form['gnz_family_member_number'] = $request->get('gnz_family_member_number');
			$form['resigned'] = $request->get('resigned');
			$form['previous_clubs'] = $request->get('previous_clubs');
			$form['resigned_comment'] = $request->get('resigned_comment');
		}

		// log any changes
		$this->_check_for_changes($form, $member);

		// check if we are changing the GNZ membership type, and if so, notify who needs to know
		if ($member->gnz_membertype_id != $request->get('gnz_membertype_id'))
		{
			$settings = new Settings();
			if ($gnz_org = Org::where('short_name', '=', 'GNZ')->first())
			{
				if ($gnz_email = $settings->get('email_new_member_to', $gnz_org))
				{
					Mail::to($gnz_email)->send(new NotifyGnzMemberTypeChange($member, $member->gnz_membertype_id, $request->get('gnz_membertype_id')));
				}
			}
		}

		$member->fill($form);
		if ($member->save())
		{
			return $this->success();
		}
		return $this->error();

	}


	protected function _check_for_changes($form, $member)
	{
		foreach ($form AS $key=>$form_item)
		{
			if ($form_item!=$member->$key)
			{
				$this->log_member_change($member, 'Update', $key, $member->$key, $form[$key]);
			}
		}
	}


	public function log_new_member($member)
	{
		$user = Auth::user();
		$now = DB::raw('NOW()');

		$changeLog = new MemberChangeLog;
		$changeLog->description = "[gliding.net.nz] User {$user->first_name} {$user->last_name} {$user->email}  (ID: {$user->id}) Created Member: {$member->first_name} {$member->last_name} for club {$member->club}.";
		$changeLog->action = 'Create';
		$changeLog->field = null;
		$changeLog->oldval = null;
		$changeLog->newval = $member->id;
		$changeLog->created = $now;
		$changeLog->id_member = $member->id;
		$changeLog->id_user = $user->id;

		$changeLog->save();
	}


	public function log_member_change($member, $action, $field, $oldval, $newval)
	{
		$user = Auth::user();
		$now = DB::raw('NOW()');

		$changeLog = new MemberChangeLog;
		$changeLog->description = "[gliding.net.nz] User  {$user->first_name} {$user->last_name} {$user->email} (ID: {$user->id}) modified {$field} of Member: {$member->first_name} {$member->last_name} (GNZ {$member->nzga_number}).";
		$changeLog->action = $action;
		$changeLog->field = $field;
		$changeLog->oldval = $oldval;
		$changeLog->newval = $newval;
		$changeLog->created = $now;
		$changeLog->id_member = $member->id;
		$changeLog->id_user = $user->id;

		$changeLog->save();
	}

	/**
	 * Similar to index, except will send email to the list.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function email(Request $request)
	{
		$memberUtilities = new MemberUtilities();

		if (Gate::denies('gnz-member')) {
			return $this->denied();
		}

		if (!$request->input('from')) return $this->error('From email is required');
		if (!$request->input('message')) return $this->error('Message is required');
		if (!$request->input('subject')) return $this->error('Subject is required');

		$query = $memberUtilities->get_filtered_members($request);

		$data['text']=$request->input('message');

		if ($members = $query->get())
		{

			Mailgun::send(['html' => 'emails.markdown-email', 'text' => 'emails.rawtext-email'], $data, function ($message) use ($request, $members) {

				foreach ($members AS $member)
				{
					$message->to($member->email, $member->first_name, ['lastname'=>$member->last_name]);
				}

				$message->from($request->input('from'), 'Gliding New Zealand Mailing List');
				$message->subject($request->input('subject'));
			});

			return $this->success();
		}
		return $this->error();
	}




	public function export(Request $request, $format='xlsx')
	{
		$extension = 'xlsx';

		switch ($format)
		{
			case 'csv':
				$extension = 'csv';
				break;
			case 'xls':
				$extension = 'xls';
				break;
		}

		//return Excel::download(new MembersExport($request), 'members.xlsx');
		return  (new MembersExport($request))->download('members.' . $extension);

	}


	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		// if (Gate::denies('gnz-member')) {
		// 	return $this->denied();
		// }
		$memberUtilities = new MemberUtilities();
		$query = $memberUtilities->get_filtered_members($request);

		// if specified, generate a CSV or excel file instead
		if ($request->input('format')=='csv' || $request->input('format')=='xls')
		{
			$members = $query->get();

			// generate a random key to identify and download the file
			$random_filename = randomkeys(10);

			// export the file in the specified format
			$file_details = Excel::create($random_filename, function($excel) use($members) {
				$excel->sheet('Sheet 1', function($sheet) use($members) {
					$sheet->fromArray($members);
				});
			})->store($request->input('format'), false, true);

			// create the download URL to return
			$return_details['url'] = URL::to('/members/download/' . $random_filename . '.' . $request->input('format'));

			return $this->success($return_details);

		}

		// otherwise paginate the results
		if ($members = $query->paginate($request->input('per-page', 50)))
		{
			//$memberUtilities->filter_view_results($members);
			return $this->success($members, TRUE);
		}
		return $this->error();
	}


	// filter out any columns that shouldn't be edited unless you have permission
	protected function _filter_edit_results(&$member)
	{
		if (Gate::denies('club-admin') && Gate::denies('view-membership')) {
			$member->makeHidden("date_of_birth");
			$member->makeHidden("access_level");
			$member->makeHidden("address_1");
			$member->makeHidden("address_2");
			$member->makeHidden("comments");
			$member->makeHidden("modified");
			$member->makeHidden("created");
			$member->makeHidden("zip_post");
		}
	}

	public function anonymous_member_dates()
	{
		$query = Member::query();
		$query->orderBy('id');

		// only get the few fields we need
		$query->select(DB::raw('if (resigned IS NOT NULL, 1, 0) AS current_member'));
		$query->addSelect('id', 'club', 'date_joined', 'date_of_qgp', 'resigned');

		$results = $query->get();

		return $this->success($results);
	}

	/**
	 * Optionally pass a date. If not provided use today.
	 */
	public function address_changes($limit_date=null)
	{
		$MemberUtilities = new MemberUtilities();
		if ($users = $MemberUtilities->get_address_changes($limit_date)) {
			return $this->success($users);
		}
		return $this->error();
	}

	/*
	Load the entire log history for this user
	 */
	public function log(Request $request, $id)
	{
		// first load the member
		if ($member = Member::find($id))
		{
			$query = MemberChangeLog::where('id_member', '=', $member->id)->orderBy('created', 'DESC');

			if ($request->has('limit')) 
			{
				$query->limit($request->input('limit'));
			}

			// if loaded, get the logs for that user
			if ($logs = $query->get())
			{
				return $this->success($logs);
			}
		}
		return $this->error();
	}


}
