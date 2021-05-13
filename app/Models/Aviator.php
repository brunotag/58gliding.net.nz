<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aviator extends Model
{
	protected $table = 'aviators';
	public $timestamps = false;

	protected $fillable = ['ts', 'device_id', 'aircraft_id', 'member_id', 'strength', 'tile_id'];
}
