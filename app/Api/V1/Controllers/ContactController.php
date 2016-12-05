<?php

namespace App\Api\V1\Controllers;

use App\Models\User;
use App\Models\Contact;
use App\Lib\Output;

use JWTAuth;
use Validator;
use Config;


use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\Password;
use Tymon\JWTAuth\Exceptions\JWTException;
use Dingo\Api\Exception\ValidationHttpException;

class ContactController extends Controller
{

    public function __construct(){

    }

    public function lists(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        try{
            $contactList = Contact::where('user_uid', $user->userUid)->get();
            $output->data = $contactList;
            $output->info = trans('message.data_success');
            $output->status = 1;
        } catch (Exception $e){
            $output->info = trans('message.data_failed');
            $output->status = 0;
        }        
        return response()->json($output);
    }

    public function get(Request $request, $contactUid){
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        try{
            $contact = Contact::where('user_uid', $user->userUid)
                                ->where('contact_uid', $contactUid)
                                ->first();
            $output->data = $contact;
            $output->info = trans('message.data_success');
            $output->status = 1;
        } catch (Exception $e){
            $output->info = trans('message.data_failed');
            $output->status = 0;
        }
        return response()->json($output);
    }

    public function insert(Request $request){

    }

    public function update(Request $request){

    }

    public function delete(Request $request){

    }

}

