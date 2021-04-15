<?php

namespace App\Http\Controllers\Api\v2;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
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

		$query = "SELECT id, thetime, X(loc) AS lat, Y(loc) AS lng, alt, speed, course, type FROM `".$table_name."` WHERE rego=? OR hex=? ORDER BY thetime";

		$points = DB::connection('ogn')->select($query, Array($rego, $rego));

		$results = [];

		$flight = 0;
		$flights = [];


		$status = 'ground';
		foreach ($points AS $key=>$point)
		{
			$point->gl = $this->_get_ground_level($point->lat, $point->lng);
			$point->agl = $point->alt - $point->gl;
			if ($point->agl<0) $point->agl = 0;

			if ($point->agl>10 && $point->speed>5) {

				if ($status=='ground')
				{
					$flight++;
					$flights[$flight]['start'] = $point->thetime;
				}
				$status='flying';
			}
			else 
			{
				if ($status=='flying')
				{
					$flights[$flight]['end'] = $point->thetime;
				}
				$status='ground';
			}
			$point->status = $status;

			$results[] = $point->thetime . ' ' . $status . ' speed: '. $point->speed . ' alt ' . $point->alt . ' agl ' . $point->agl; 
		}

		return $this->success(array($flights, $results));
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
