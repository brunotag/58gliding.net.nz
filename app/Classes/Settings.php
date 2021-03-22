<?php
namespace App\Classes;

use App\Models\Setting;

class Settings
{

	public $settings;


	public function get($name, $org=null)
	{
		$query = Setting::where('name', $name);
		
		if ($org===null)
		{
			$query = $query->whereIsNull('org_id');
		}
		else
		{
			$query = $query->where('org_id', '=', $org->id);
		}
		$result = $query->get();
		return $result->value;
	}
}