<?php

namespace App\Listeners;

use Log;
use App\Events\GoogleSigninEvent;
use App\Events\FacebookSigninEvent;
use App\Events\ITimeSigninEvent;
use App\Events\ITimeSignupEvent;

use App\Models\Calendar;
use App\Models\UserCalendar;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserSubscriber
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function onITimeSignin($event){

        Log::info('onITimeSignin');
    }

    public function onGoogleSignin($event){

        Log::info('onGoogleSignin');
    }

    public function onFacebookSignin($event){

        Log::info('onFacebookSignin');
    }

    public function onITimeSignup(ITimeSignupEvent $event){
        $user = $event->user;
        Log::info('onITimeSignup:'. $user->userId );
        $calendar = new Calendar();
        $calendar->calendarUid = $user->userUid;
        $calendar->iCalUID = 'icaluid'.$calendar->calendarUid;
        $calendar->summary = 'work';
        $calendar->color= '#cccccc';
        $calendar->save();

        $uc = new UserCalendar();
        $uc->calendarUid = $calendar->calendarUid;
        $uc->userUid = $user->userUid;
        $uc->groupUid = 1;
        $uc->groupTitle = "iTime";
        $uc->access = 'owner';
        $uc->status = 'accepted';
        $uc->save();

    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\ITimeSignupEvent',
            'App\Listeners\UserSubscriber@onITimeSignup'
        );
        $events->listen(
            'App\Events\FacebookSigninEvent',
            'App\Listeners\UserSubscriber@onFacebookSignin'
        );
        $events->listen(
            'App\Events\GoogleSigninEvent',
            'App\Listeners\UserSubscriber@onGoogleSignin'
        );
        $events->listen(
            'App\Events\ITimeSigninEvent',
            'App\Listeners\UserSubscriber@onITimeSignin'
        );
    }
}
