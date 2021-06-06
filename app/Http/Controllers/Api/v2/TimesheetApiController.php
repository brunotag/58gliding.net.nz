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

include_once(app_path() . '/Classes/SRTMGeoTIFFReader.php');

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
		$search_pilots = false; // keep track of which tracker types have been used. So we only search for pilots if the tracker supports it.

		$low_speed_threashold = 9;
		$status = 'ground';


		// check for duplicate points
		foreach ($points AS $key=>$point)
		{
			if (isset($points[$key+1]))
			{
				$next_point = $points[$key+1];
				if ($next_point->thetime==$point->thetime)
				{
					unset($points[$key+1]);
				}
			}
		}

		// fix up the indexes of the array if we removed any duplicates
		$points = array_values($points);


		foreach ($points AS $key=>$point)
		{
			$no_altitude=false;

			// check if we have altitude, if not maybe this is a SPOT or glitch in the data.
			// Do the same as we do when graphing the data, and make this alt the same as previous.
			if ($point->alt==null)
			{
				$no_altitude=true;
				$point->alt = $prev_alt;

				// low speed for devices with no altitude is a lot lower because
				// it's probably a SPOT, they might have not travelled far in 10 mins,
				// so speed is much lower
				$low_speed_threashold = 1;
			}

			$point->gl = $this->_get_ground_level($point->lat, $point->lng);
			$point->agl = $point->alt - $point->gl;
			if ($point->agl<0) $point->agl = 0;

			$point->this_time = null;
			$point->next_time = null;
			$point->speed_next = null;
			$point->next_agl = null;
			$point->distanceToNext = null;


			// only search for pilots if we encounter a tracker that supports it
			if ($point->type==11) $search_pilots=true;


			// get the next point if it exists
			if (isset($points[$key+1]))
			{
				$next_point = $points[$key+1];
				// calc the speed to the next point from the location data
				// $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo (in meters)
				$point_time = new Carbon($point->thetime);
				$next_point_time = new Carbon($next_point->thetime);
				$point->distanceToNext = haversineGreatCircleDistance($point->lat, $point->lng, $next_point->lat, $next_point->lng);
				//if ($point->lat==$next_point->lat)
				// if ($point->type==5)
				// {
				// 	print_r($point);
				// 	print_r($next_point);
				// 	print_r('----------');
				// }

				$point->this_time = $point->thetime;
				$point->next_time = $next_point->thetime;
				if ($point_time->diffInSeconds($next_point_time)!=0)
				{
					$point->speed_next = round($point->distanceToNext / ($point_time->diffInSeconds($next_point_time)));
				}
				$point->next_agl = $this->_get_ground_level($next_point->lat, $next_point->lng);
			}


			if ($point->agl!==null || $no_altitude) {

				// to trigger a take off we need to be at least 30m AGL
				if ($point->agl>20 || $no_altitude) {

					// only start flying if we either have a ground speed >9m/s
					if ($point->speed>$low_speed_threashold
						|| ($point->speed===null && $point->speed_next>$low_speed_threashold))
					{
						// check if the next point actually is off the ground (e.g. the current one might be a spot and the next one isn't)
						//echo $point->next_agl; exit();
						if ($point->next_agl!==null && $point->next_agl>20 || $point->next_agl===null)
						{
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
				if ($status=='flying' && ($point->agl < 25 || $no_altitude))
				{
					if (($point->speed===null && $point->speed_next<$low_speed_threashold)
						|| ($point->speed!==null && $point->speed<$low_speed_threashold && $point->speed_next<$low_speed_threashold))
					{
						$status='ground';
					}
				}
			}
			$point->status = $status;
			$prev_alt = $point->alt;

			$results[] = $point->type . ' ' . $point->thetime . ' ' . $status. ' distance ' . $point->distanceToNext . ' speed: '. $point->speed . ' speedToNext: '.  $point->speed_next  . ' alt ' . $point->alt . ' agl ' . $point->agl;
			//. ' this_time: '. $point->this_time . ' next_time: '.  $point->next_time
		}


		// load the pilots who flew this flight, but only if we've found a tracker that supports it.
		if ($aircraft )
		{
			foreach ($flights AS $key=>$flight)
			{
				// first remove this flight if it's less than 32 seconds long, as thats probably not a flight.
				if ($flight['duration'] < 32) unset($flights[$key]);
				else
				{
					if ($search_pilots)
					{
						$query = "SELECT first_name, last_name, gnz_member.id, count(aviators.member_id) AS hitcount, avg(aviators.strength) AS strength FROM aviators LEFT JOIN gnz_member ON aviators.member_id=gnz_member.id WHERE aircraft_id=".$aircraft->id." AND ts>'".$flight['start']."' AND ts<'" . $flight['end'] . "' GROUP BY aviators.member_id ORDER BY hitcount DESC, strength DESC";
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

			// fix up the indexes of the array if we removed any duplicates
			$flights = array_values($flights);
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
