<?php

namespace App\Api\V1\Controllers;

use App\Models\User;

use App\Lib\Output; 

use App\Events\GoogleSigninEvent;
use App\Events\FacebookSigninEvent;
use App\Events\ITimeSigninEvent;
use App\Events\ITimeSignupEvent;

use App\Jobs\SendReminderEmail;

use JWTAuth;
use Validator;
use Config;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\Password;
use Tymon\JWTAuth\Exceptions\JWTException;
use Dingo\Api\Exception\ValidationHttpException;


class UserController extends Controller
{
    use Helpers;

    public function signin(Request $request){
        $credentials = $request->only(['userId', 'password']);

        info($credentials);
        $validator = Validator::make($credentials, [
            'userId' => 'required',
            'password' => 'required',
        ]);

        $output = new Output();
        if($validator->fails()) {
            $output->info = $validator->errors()->all()[0];
            $output->status = -1;
            return response()->json($output);
        }

        try {
            // because attempt function directly manipulates db, 
            // it doesn't go through the model field map
            $jwtCredentials = [
                'user_id' => $credentials['userId'],
                'password' => $credentials['password']
            ];
            if (! $token = JWTAuth::attempt($jwtCredentials)) {
                $output->info = trans('auth.failed');
                $output->status = -2;
                return response()->json($output);
            }
            $user = JWTAuth::toUser($token);
            $data = (object)[];
            $data->user = $user;
            $data->token = $token;
            $data->account = [];
            $data->setting = [];
            $output->data = $data;
            $output->info = trans('message.data_success');
            $output->status = 1;
            return response()->json($output);
        } catch (JWTException $e) {
            $output->data = [];
            $output->info = trans('auth.token_failed');
            $output->status = -3;
            return response()->json($output);
        }
    }

    public function signinByGoogle(Request $request){

        $user = User::all()[0];
        // event(new GoogleSigninEvent($user));
        // demo for firing a custom event
        // event(new GoogleSigninEvent($user));
        // demo for dispatching a delayed job
        // $job = (new SendReminderEmail($user))
        //         ->delay(Carbon::now()->addMinutes(1));
        // $job = new SendReminderEmail($user);
        // dispatch($job);
        // 
        
        $event = \App\Models\Event::builder(1, 1)->first();
        $ev = new \App\Events\EventCreated($event);
        event($ev);

        return 'signinByGoogle: user=' . $user->userUid;
    }

    public function signinByFacebook(Request $request){

        $user = User::all()[0];
        event(new FacebookSigninEvent($user));
        return 'signinByFacebook';
    }

    public function signup(Request $request){
        $signupFields = Config::get('boilerplate.signup_fields');
        $hasToReleaseToken = Config::get('boilerplate.signup_token_release');

        $userData = $request->only($signupFields);

        $validator = Validator::make($userData, Config::get('boilerplate.signup_fields_rules'));

        if($validator->fails()) {
            throw new ValidationHttpException($validator->errors()->all());
        }

        User::unguard();
        $user = User::create($userData);
        User::reguard();

        if(!$user->id) {
            return $this->response->error('could_not_create_user', 500);
        }
        
        return $user;
    }


    public function signout(){
        return 'sign out';
    }

    public function refreshToken(Request $request){
        try {
            $newToken = JWTAuth::parseToken()->refresh();
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'token_expired'], $e->getStatusCode());
        } catch (JWTException $e) {
            return response()->json(['error' => 'token_invalid'], $e->getStatusCode());
        }
        return response()->json(['token' => $newToken]);
    }

    public function lists(Request $request){
        $currentUser = JWTAuth::parseToken()->authenticate();
        // $ret = [
        //     'user'=>$currentUser
        // ];
        return $currentUser->toArray();
    }

    /**
     * test for camel tranformation
     * @return
     */
    public function test(){
        // $user= User::all();
        // $user = User::where('user_id', 'johncdyin@gmail.com');
        // var_dump($user->first()->to);
        // return $user->first()->toArray();
        // return 'user/test';
        
        $output = new Output();
        $output->data = User::all()->toArray();
        $output->info = 'success';
        $output->status = 1;
        return response()->json($output);
    }
}
