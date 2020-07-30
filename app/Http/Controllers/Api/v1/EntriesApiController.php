<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Api\ApiController;
use App\Models\Entry2;

class EntriesApiController extends ApiController
{
	public function index(request $request)
	{
		$query = Entry2::query();

		if ($entries = $query->get())
		{
			return $this->success($entries);
		}
		return $this->error();
	}

	

	/**
	* Create the specified resource.
	*
	* @param  int  $id
	* @return \Illuminate\Http\Response
	*/
	public function store(request $request)
	{
		$input = $request->all();


		$entry = new Entry2;
		$entry->editcode = randomkeys(12);
		$entry->save();

		// create a new random code for it
		// 

		if (1)
		{
			return $this->success($entry);
		}
		return $this->error();
	}
}