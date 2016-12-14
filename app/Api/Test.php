<?php
namespace app\Api;
use App\Models\User;
use App\Http\Controllers\Controller;

class Test extends Controller
{
	public function output()
	{
		echo "hello";
	}
    public function login()
	{
		echo "login";
	}
}