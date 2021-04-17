<?php

namespace App\Http\Controllers\Api\v2;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use Carbon\Carbon;
use App\Models\Aircraft;
use SRTMGeoTIFFReader;
use Gate;
use Auth;
use DB;

include(app_path() . '/Classes/SRTMGeoTIFFReader.php');

class TimesheetApiController extends ApiController
{
	/**
	 * Generate a list of flights from a day
	 * /api/v2/timesheet/generate/date/site
	 * 
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function generate(Request $request, $date)
	{
		$day_date = $this->_get_table_name($date);


		return $this->error(); 
	}



	/**
	 * Generate a list of flights from a day
	 * /api/v2/timesheet/generate/date/site
	 * 
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function aicraft(Request $request, $date, $rego)
	{
		$table_name = $this->_get_table_name($date);

		$hex = null;
		if ($aircraft = Aircraft::where('rego', '=', 'ZK-'.$rego)->orWhere('flarm', '=', $rego)->first())
		{
			if ($aircraft->flarm!='') $hex = $aircraft->flarm;
		}

		$query = "SELECT id, thetime, X(loc) AS lat, Y(loc) AS lng, alt, speed, course, type FROM `".$table_name."` WHERE rego=? OR hex=? ORDER BY thetime";

		$points = DB::connection('ogn')->select($query, Array($rego, $hex));

		$results = [];

		$flight = 0;
		$flights = [];
		$prev_alt = null;

		$status = 'ground';
		foreach ($points AS $key=>$point)
		{

			// check if we have altitude, if not maybe this is a SPOT or glitch in the data.
			// Do the same as we do when graphing the data, and make this alt the same as previous.
			if ($point->alt==null)
			{
				$point->alt = $prev_alt;
			}

			$point->gl = $this->_get_ground_level($point->lat, $point->lng);
			$point->agl = $point->alt - $point->gl;
			if ($point->agl<0) $point->agl = 0;

			$point->this_time = null;
			$point->next_time = null;
			$point->speed_next = null;
			$point->next_agl = null;

			// calc the speed to the next point from the location data
			if (isset($points[$key+1]))
			{
				// $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo (in meters)
				$point_time = new Carbon($point->thetime);
				$next_point_time = new Carbon($points[$key+1]->thetime);
				$point->distanceToNext = haversineGreatCircleDistance($points[$key]->lat, $points[$key]->lng, $points[$key+1]->lat, $points[$key+1]->lng);
				$point->this_time = $point->thetime;
				$point->next_time = $points[$key+1]->thetime;
				if ($point_time->diffInSeconds($next_point_time)!=0)
				{
					$point->speed_next = round($point->distanceToNext / ($point_time->diffInSeconds($next_point_time)));
				}
				$point->next_agl = $this->_get_ground_level($points[$key+1]->lat, $points[$key+1]->lng);
			}


			if ($point->agl!==null) {

				// to trigger a take off we need to be 30m AGL or 20m/s in speed
				if ($point->agl > 30 || $point->speed_next>20) {

					// only start flying if we either have a ground speed >9m/s OR we don't have speed at all
					if ($point->speed>9 || ($point->speed===null && $point->speed_next>9)) {
						if ($status=='ground')
						{
							// start a new flight
							$flight++;
							$flights[$flight]['start'] = $point->thetime;
							$flights[$flight]['end'] = null;
							$flights[$flight]['duration'] = null;
						}
						$status='flying';
					}
				}
				
				if ($status=='flying')
				{
					$flights[$flight]['end'] = $point->thetime;
					// calculate the duration
					// calculate the duration. We always do this in case we are still flying at the end of the data.
					$start = new Carbon($flights[$flight]['start']);
					$end = new Carbon($flights[$flight]['end']);
					$flights[$flight]['duration'] = $start->diffInSeconds($end);
				}

				// check if we've landed, and check the next point is also below our threashold 
				// to filter out weird GPS data
				if ((($point->agl < 30) && (($point->speed===null && $point->speed_next<9) || $point->speed<9)))
				{
					if ($status=='flying')
					{
						$status='ground';
					}
				}
			}
			$point->status = $status;
			$prev_alt = $point->alt;

			$results[] = $point->thetime . ' ' . $status . ' speed: '. $point->speed . ' speedToNext: '.  $point->speed_next  . ' alt ' . $point->alt . ' agl ' . $point->agl; 
			//. ' this_time: '. $point->this_time . ' next_time: '.  $point->next_time
		}


		// load the pilots who flew this flight
		if ($aircraft)
		{
			foreach ($flights AS $key=>$flight)
			{
				// first remove this flight if it's less than 10 seconds long, as thats not actually a flight.
				if ($flight['duration'] < 20) unset($flights[$key]);
				else
				{
					$query = "SELECT first_name, last_name, gnz_member.id, count(aviators.member_id) AS count, avg(aviators.strength) AS strength FROM aviators LEFT JOIN gnz_member ON aviators.member_id=gnz_member.id WHERE aircraft_id=".$aircraft->id." AND ts>'".$flight['start']."' AND ts<'" . $flight['end'] . "' GROUP BY aviators.member_id";
					if ($aviators = DB::select($query))
					{
						$flights[$key]['pilots'] = $aviators;
					}
					else
					{
						$flights[$key]['pilots'] = null;
					}
				}
			}
		}
		

		return $this->success(array($flights, $results));
		return $this->success($flights);
	}


	protected function _get_table_name($dayDate)
	{
		if ($dayDate==null || $dayDate=='' || $dayDate=='null') return false;
		$date = new \DateTime($dayDate);
		$table_name = 'data' . $date->format('Ymd');
		return $table_name;
	}

	protected function _get_ground_level($lat, $long)
	{
		try
		{
			$dataReader = new SRTMGeoTIFFReader(storage_path() . '/app/geotiffdata');
			$dataReader->showErrors = false;
			if ($elevation = $dataReader->getElevation($lat, $long))
			{
				if ($elevation<0) return null;
				return $elevation;
			}
		}
		catch(\Exception $e)
		{
			return null;
		}
		

		return null;
	}


}
