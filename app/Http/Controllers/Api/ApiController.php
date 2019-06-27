<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Auth;
use DB;

class ApiController extends Controller
{
	protected $data;

	public function __construct()
	{
		DB::enableQueryLog();
		$this->data['success']=false;
		$this->data['http_code']=500;
	}

	public function success($data=[], $paginated=false)
	{
		if ($paginated)
		{
			$item = $data->toArray();
			$data = $item['data'];
			unset($item['data']);
			$this->data = array_merge($this->data, $item);
		}
		$this->data['data'] = $data;
		$this->data['success']=true;
		$this->data['http_code']=200;
		$this->_get_db_queries();
		return $this->data;
	}

	public function not_found($message="Not Found")
	{
		$this->data['error']=$message;
		$this->data['success']=false;
		$this->data['http_code']=404;
		$this->_get_db_queries();
		return response()->json($this->data)->setStatusCode(404);
	}

	public function bad_request($message="Bad Request")
	{
		$this->data['error']=$message;
		$this->data['success']=false;
		$this->data['http_code']=400;
		$this->_get_db_queries();
		return $this->data;
	}

	public function error($message="An Unknown Error Occured")
	{
		$this->data['error']=$message;
		$this->data['success']=false;
		$this->data['http_code']=500;
		$this->_get_db_queries();
		return response()->json($this->data)->setStatusCode(500);
	}

	public function denied($message="Permission Denied")
	{
		$this->data['error']=$message;
		$this->data['success']=false;
		$this->data['http_code']=403;
		$this->_get_db_queries();
		return response()->json($this->data)->setStatusCode(403);
	}

	protected function _get_db_queries()
	{
		$this->data['queries']=DB::getQueryLog();
	}

	public function fetch_user()
	{
		$user = Auth::user();
		if ($user==null)
		{
			// try OAuth user
			if ($userID = Authorizer::getResourceOwnerId())
			{
				// fetch the user
				$user = User::find($userID);
			}
		}
		return $user;
	}
}