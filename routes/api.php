<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/user', function (Request $request) {
	return $request->user();
})->middleware('auth:api');


/* Unversionsed APIs */
Route::group(['namespace' => 'Api', 'middleware' => 'auth:api'], function()
{
	Route::resource('days', 'DayAPIController');
	Route::post('/days/deactivate',  'DayAPIController@deactivate');
	Route::resource('duties', 'DutyAPIController');
	Route::resource('roster', 'RosterAPIController');
});


Route::group(['namespace' => 'Api'], function()
{
	Route::resource('events', 'EventsAPIController');
	Route::get('/events/{event_id}/soaringspot/tasks', 'SoaringSpotAPIController@tasks');
	Route::get('/events/{event_id}/soaringspot/tasks/{task_id}', 'SoaringSpotAPIController@task');
});


/* v2 redesigned APIs. v1 remains as is below */
Route::group(['prefix'=>'v2', 'namespace' => 'Api\v2'], function()
{
	// all pings for all aircraft on a specific day
	Route::get('/tracking/{dayDate}/{points}',  'Tracking2ApiController@points'); 
	Route::get('/tracking/{dayDate}/aircraft/{key}',  'Tracking2ApiController@aircraft');
});


/* v1 API */
Route::group(['prefix'=>'v1', 'namespace' => 'Api\v1'], function()
{
	Route::get('/fetchspots',  'TrackingApiController@fetchSpots');
	Route::get('/fetchinreach',  'TrackingApiController@fetchInReach');
	
	Route::get('/aircraft/{rego}', 'AircraftApiController@rego')
		->where('rego','(?i)ZK-[A-Z]{3}(?-i)');

	Route::resource('aircraft', 'AircraftApiController', ['only' => [
		'index', 'show', 'update'
	]]);
	Route::post('/aircraft/hexes',  'AircraftApiController@hexes'); // given a list of hexes, get the aircraft details

	Route::get('/orgs',  'OrgApiController@index');
	Route::get('/orgs/{id}',  'OrgApiController@show');

	Route::post('/tracking/insert',  'TrackingApiController@insert');
	Route::get('/tracking/days',  'TrackingApiController@days');
	Route::get('/tracking/{dayDate}/hexes',  'TrackingApiController@dayHexes'); // all unique hex codes on that day
	Route::get('/tracking/{dayDate}/pings',  'TrackingApiController@latestDayPings'); // last ping on that day for all hexes
	Route::get('/tracking/{dayDate}/pings/{pointsPerHex}',  'TrackingApiController@dayPings'); // x number of pings per hex for a day
	Route::get('/tracking/{dayDate}/{hex}/pings',  'TrackingApiController@dayHexPings'); // all pings for a hex code on a day
	Route::post('/electron/', 'TrackingApiController@electron');
	Route::get('/electron/', 'TrackingApiController@electron');
	Route::post('/spotnz', 'TrackingApiController@spotnz');
	Route::get('/spotnz', 'TrackingApiController@spotnz');

	Route::resource('/ratings', 'RatingsApiController', ['only' => [
		'index'
	]]);

	// Not sure an uploads API is a good idea, as we can't handle permissions on it.
	// Route::resource('/uploads', 'UploadsApiController', ['only' => [
	// 	'destroy'
	// ]]);
		
	// Route::resource('days', 'DayApiController', ['only' => [
	// 	'index', 'show', 'destroy', 'create', 'update'
	// ]]);

	Route::get('/roles',  'RolesApiController@index');
	Route::get('/badges',  'BadgesApiController@index');
	Route::get('/badges/{id}',  'BadgesApiController@show');

	Route::get('/waypoints',  'WaypointsApiController@index');
	Route::get('/waypoints/lists',  'CupsApiController@index');
	Route::get('/waypoints/{id}',  'WaypointsApiController@show');
	Route::get('/waypoints/lists/{id}',  'CupsApiController@show');

	// special anonymous member stats for external use
	Route::get('/members/anonymous-stats', 'MembersApiController@anonymous_member_dates');
	Route::get('/members/address-changes', 'MembersApiController@address_changes');
	Route::get('/members/address-changes/{limit_date}', 'MembersApiController@address_changes');

	Route::resource('/fleets', 'FleetsApiController', ['only' => [
		'index', 'show', 'store'
	]]);
	Route::post('/fleets/{fleet_id}/add',  'FleetsApiController@add');
	Route::post('/fleets/{fleet_id}/remove',  'FleetsApiController@remove');

	// get settings
	Route::get('/settings/{orgId}',  'SettingsApiController@getOrg'); // load settings for an org
	Route::get('/settings',  'SettingsApiController@get'); // load global settings

	Route::post('/entries/{editcode}',  'EntriesApiController@update');
	Route::get('/entries/code/{editcode}',  'EntriesApiController@showCode');
	Route::resource('/entries', 'EntriesApiController', ['only' => [
		'index', 'create', 'store', 'show'
	]]);
	Route::get('/classes',  'ClassesApiController@index');

	// list, link or unlink a class to an event
	Route::get('/events/{id}/classes',  'ClassesApiController@event');
	Route::post('/classes/{id}/link',  'ClassesApiController@link');
	Route::post('/classes/{id}/unlink',  'ClassesApiController@unlink');

	Route::get('/membertypes', 'MembertypeApiController@index');

	Route::group(['middleware' => ['auth:api']], function () {

		Route::post('/settings/{orgId}',  'SettingsApiController@insertOrg');
		Route::post('/settings',  'SettingsApiController@insert');

		Route::post('/admin/import-flarm', 'AdminApiController@import_flarm');
		Route::post('/admin/import-aircraft-from-caa', 'AdminApiController@import_aircraft_from_caa');
		Route::post('/admin/email-address-changes', 'AdminApiController@email_address_changes');


		Route::resource('/affiliates', 'AffiliatesApiController', ['only' => [
			'update', 'create', 'store', 'delete', 'destroy'
		]]);

		Route::resource('/membertypes', 'MembertypeApiController', ['only' => [
			'store', 'create', 'destroy', 'update'
		]]);


		Route::post('/membership/',  'MembershipApiController@create'); // create a new relationship between member and organisation
		Route::post('/users/{userID}/membership',  'MembershipApiController@get'); //  get a list of orgs this member belongs to
		

		Route::get('/ratings/report',  'RatingMemberApiController@ratingsReport');
		Route::get('/members/export/{format}', 'MembersApiController@export');
		
		Route::get('/users',  'UsersApiController@index');
		Route::get('/users/{userID}/roles',  'RolesApiController@user_roles');
		Route::post('/users/{userID}/roles',  'RolesApiController@add_user_role');
		Route::delete('/role-user/{roleUserID}',  'RolesApiController@delete_user_role');
		Route::post('/role-user/{roleUserID}',  'RolesApiController@update_user_role');

		Route::resource('/members/{member_id}/ratings', 'RatingMemberApiController', ['only' => [
			'index', 'store', 'create'
		]]);
		Route::post('/members/{member_id}/ratings/upload',  'RolesApiController@upload');
		Route::get('/members/{member_id}/log',  'MembersApiController@log');
		Route::post('/members/{member_id}/request-approval',  'MembersApiController@requestApproval');
		Route::post('/members/{member_id}/resign-gnz',  'MembersApiController@resignGnz');
		Route::post('/members/{member_id}/set-next-gnz-number',  'MembersApiController@setGnzNumber');
		Route::get('/members/{member_id}/find-duplicates',  'MembersApiController@findDuplicates');


		Route::get('/members/{member_id}/ratings/{rating_member_id}',  'RatingMemberApiController@get');
		Route::delete('/members/{member_id}/ratings/{rating_id}/upload/{upload_id}',  'RatingMemberApiController@destroyFile');
		Route::post('/rating-member/{id}/upload',  'RatingMemberApiController@upload');
		Route::delete('/members/{member_id}/ratings/{rating_id}',  'RatingMemberApiController@destroy');


		Route::post('/addmembers',  'MembersApiController@store');

		Route::resource('/members', 'MembersApiController', ['only' => [
			'index', 'show', 'update', 'store'
		]]);
		Route::post('/members/email',  'MembersApiController@email'); // ability to send emails to the membership

		Route::resource('/achievements', 'AchievementsApiController', ['only' => [
			'index', 'show', 'update', 'destroy', 'store', 'delete'
		]]);
	});
});

