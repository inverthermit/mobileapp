<?php

namespace App\Api\V1\Controllers;

use App\Models\User;
use App\Models\Calendar;
use App\Models\UserCalendar;

use App\Lib\Output;

use JWTAuth;
use Validator;
use Config;
use Uuid;

use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\Password;
use Tymon\JWTAuth\Exceptions\JWTException;
use Dingo\Api\Exception\ValidationHttpException;


class CalendarController extends Controller{

    public function __construct(){

    }

    public function lists(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        $calendarList = UserCalendar::builder($user->userUid, '')->get();
        $output->data = $calendarList;
        $output->info = trans('message.data_success');
        $output->status = 1;
        return response()->json($output);
    }

    public function get(Request $request, $calendarUid){
        // need to check the access of user
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        $calendar = UserCalendar::builder($user->userUid, $calendarUid)->first();
        if(!empty($calendar)){
            $output->data = $calendar;
            $output->info = trans('message.data_success');
            $output->status = 1;
        }else{
            $output->data = [];
            $output->info = trans('message.data_failed');
            $output->status = 0;
        }
        return response()->json($output);
    }

    /**
     * method: post,
     * input: 
     *     summary: the title of calendar
     *     color: display color, e.g., #fffccc
     * @param  Request $request [description]
     * @return [calendar]
     */
    public function insert(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        try{
            $calendarUid = UUid::generate()->string;
            $calendar = new Calendar();
            $calendar->calendarUid = $calendarUid;
            $calendar->iCalUID = 'icaluid:'.$calendar->calendarUid;
            $calendar->summary = $request->input('summary', 'no title');
            $calendar->color= $request->input('color', '#f2f2f2');
            $calendar->save();

            $uc = new UserCalendar();
            $uc->calendarUid = $calendar->calendarUid;
            $uc->userUid = $user->userUid;
            $uc->groupUid = 1;
            $uc->groupTitle = "iTime";
            $uc->access = 'owner';
            $uc->status = 'accepted';
            $uc->save();

            $lastInsertCalendar = UserCalendar::builder($user->userUid, $calendarUid)->first();
            $output->data = $lastInsertCalendar;
            $output->info = trans('message.data_success');
            $output->status = 1;
        }catch(\Exception $e){
            var_dump($e->getMessage());
            $output->info = trans('message.data_failed');
            $output->status = -1;
        }
        return response()->json($output);
    }

    public function share(Request $request){

    }







}