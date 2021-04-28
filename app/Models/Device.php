<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
	protected $connection = 'ogn';
	protected $table = 'devices';

	protected $fillable = ['device_id', 'last_turned_on', 'ip'];
}
