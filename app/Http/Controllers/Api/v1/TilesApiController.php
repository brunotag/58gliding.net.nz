<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests;
use App\Models\Aircraft;
use App\Models\Aviator;
use App\Models\Tile;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Schema;
use Auth;
use DateTime;
use DateTimeZone;


class TilesApiController extends ApiController
{

	public function index(Request $request)
	{
		// get the current user
		$user = Auth::user();

		$query = Tile::orderBy('last_seen', 'DESC')->orderBy('last_strength', 'DESC')->with('LastAircraft')->with(array('member' => function($query) {
			$query->select('id','first_name', 'last_name');
		}));

		if ($request->input('new', false)==true) $query->where('last_seen', '>', Carbon::now()->subHours(1)->toDateTimeString());
		if ($request->input('existing', true)===true) $query->whereNotNull('member_id');

		// load all tiles
		if ($tiles = $query->get())
		{
			return $this->success($tiles);
		}
		return $this->error(); 
	}



	public function update(Request $request, $id)
	{
		if (!$tile = Tile::find($id))
		{
			return $this->not_found();
		}


		if ($request->has('member_id')) $tile->member_id = $request->input('member_id');
		if ($request->has('note')) $tile->note = $request->input('note');
		if ($request->has('type')) $tile->type = $request->input('type');
		$tile->save();
		return $this->success($tile);

	}


}