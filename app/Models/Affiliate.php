<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
	protected $table="affiliates";

	protected $dates = ['join_date','end_date'];
	protected $with = ['Membertype']; // eager load the membertype

	public function org()
	{
		return $this->belongsTo('App\Models\Org');
	}

	public function membertype()
	{
		return $this->belongsTo('App\Models\Membertype');
	}


	protected $casts = [
		'join_date'=>'date',
		'end_date'=>'date'
	];
}