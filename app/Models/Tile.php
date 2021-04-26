<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tile extends Model
{
	protected $table = 'tiles';

	protected $fillable = ['hex', 'member_id', 'type', 'last_seen', 'last_aircraft_id', 'last_strength'];


	public function LastAircraft()
	{
		return $this->belongsTo('App\Models\Aircraft', 'last_aircraft_id');
	}

	public function Member()
	{
		return $this->belongsTo('App\Models\Member', 'member_id');
	}
}
