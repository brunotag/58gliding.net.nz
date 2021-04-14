<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aviator extends Model
{
	protected $table = 'aviators';

	protected $fillable = ['timestamp', 'device_id', 'aircraft_id', 'member_id', 'strength'];
}
