<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Http\Controllers\Controller;

class Test extends Controller
{
	public function output()
	{
		echo "hello<br>";
		$model = User::findOrFail(1);
		echo $model->nickname;
	}
}