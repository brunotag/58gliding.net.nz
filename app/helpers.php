<?php
//make any string a simple string that is safe for variable names, filenames
function simple_string($origString, $dash_character='-', $extra='') {
	
	// strip out any single quotemarks for words like tim's
	$newString = str_replace("'", '', $origString);
	
	// replaces any non alphanumeric characters with dashes
	$newString = preg_replace("#[^A-Za-z0-9{$extra}]#", "{$dash_character}", $newString);
	
	// strips any exta consecutive dashes
	$newString = preg_replace("#$dash_character+#", "$dash_character", $newString);
	
	// remove trailing and first character underscores if they exist
	if (substr($newString, -1)=="$dash_character") $newString=substr($newString, 0, -1);
	if (substr($newString, 0, 1)=="$dash_character") $newString=substr($newString, 1);
	
	return $newString;
}

function safe_filename($string)
{
	// replaces any non alphanumeric characters with dashes
	$string = preg_replace("#[^A-Za-z0-9\.]#", "-", $string);
	return $string;
}


function randomkeys($length) {
    $pattern = "123890abhijklmnopqrscdefgt4567uvwxyz";
    $key  = $pattern{rand(0,35)};
    for($i=1;$i<$length;$i++)
    {
        $key .= $pattern{rand(0,35)};
    }
    return $key;
}

// General singleton class.
class AuthCache {
	// Hold the class instance.
	private static $instance = null;

	// The constructor is private
	// to prevent initiation with outer code.
	private function __construct()
	{
		// The expensive process (e.g.,db connection) goes here.
	}

	// The object is created from within the class itself
	// only if the class has no instance.
	public static function getInstance()
	{
		if (self::$instance == null)
		{
			self::$instance = new AuthCache();
		}

		return self::$instance;
	}
}

/**
 * Calculates the great-circle distance between two points, with
 * the Haversine formula.
 * @param float $latitudeFrom Latitude of start point in [deg decimal]
 * @param float $longitudeFrom Longitude of start point in [deg decimal]
 * @param float $latitudeTo Latitude of target point in [deg decimal]
 * @param float $longitudeTo Longitude of target point in [deg decimal]
 * @param float $earthRadius Mean earth radius in [m]
 * @return float Distance between points in [m] (same as earthRadius)
 */
function haversineGreatCircleDistance(
  $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
{
  // convert from degrees to radians
  $latFrom = deg2rad($latitudeFrom);
  $lonFrom = deg2rad($longitudeFrom);
  $latTo = deg2rad($latitudeTo);
  $lonTo = deg2rad($longitudeTo);

  $latDelta = $latTo - $latFrom;
  $lonDelta = $lonTo - $lonFrom;

  $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
    cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
  return $angle * $earthRadius;
}
