<?php

namespace App\Listeners;

use App\Jobs\MessageType;
use Log;
use App\Events\EventCreated;
use App\Events\EventConfirmed;
use App\Events\EventUpdated;
use App\Events\EventDeleted;
use App\Events\EventAccepted;
use App\Events\EventQuit;

use App\Jobs\SendNotification;
use App\Jobs\SendReminderEmail;

use App\Models\Event;
use App\Models\Calendar;
use App\Models\UserCalendar;
use App\Models\User;


use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class EventSubscriber
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

    public function onEventCreated(EventCreated $ev){
        $event = $ev->event;

        $data = [

            'event' => $event->toArray()

        ];

        $jobNotification = new SendNotification($data,MessageType::CreateGroupEvent);
        dispatch($jobNotification);
        Log::info('onEventCreated'. $event->summary);

    }

    public function onEventConfirmed(EventConfirmed $ev){
        $event = $ev->event;
        // Log::info($event);

        $data = [

            'event' => $event->toArray()

        ];


        $jobNotification = new SendNotification($data,MessageType::ConfirmGroupEvent);
        dispatch($jobNotification);

        Log::info('onEventConfirmed:'. $ev->event->summary);

    }

    public function onEventUpdated(EventUpdated $ev){
//        Log::info('onEventUpdated'. $ev->event->summary,MessageType::UpdateGroupEvent);
        // $info = trans('message.update_fields');

        $event = $ev->event;

        $data = [

            'event' => $event->toArray(),
            'inInvitees' => $ev->inInvitees,
            'outInvitees' => $ev->outInvitees,
            'curInvitees' => $ev->curInvitees,
            'updatedFields' => $ev->updatedFields
        ];


        Log::info($ev->inInvitees);
        Log::info($ev->outInvitees);
        Log::info($ev->curInvitees);
        Log::info($ev->updatedFields);

        $jobNotification = new SendNotification($data,MessageType::UpdateGroupEvent);
        dispatch($jobNotification);


    }

    public function onEventDeleted(EventDeleted $ev){
        Log::info('onEventDeleted'. $ev->event->summary);

        $event = $ev->event;

        $data = [

            'event' => $event->toArray()

        ];

        $jobNotification = new SendNotification($data,MessageType::DeleteGroupEvent);
        dispatch($jobNotification);

    }

    public function onEventAccepted(EventAccepted $ev){
        Log::info('onEventAccepted'. $ev->event->summary);

        $event = $ev->event;

        $data = [

            'event' => $event->toArray()

        ];


        $jobNotification = new SendNotification($data,MessageType::AcceptGroupEvent);
        dispatch($jobNotification);



    }

    public function onEventQuit(EventQuit $ev){
        Log::info('onEventQuit'. $ev->event->summary);

        $event = $ev->event;

        $data = [

            'event' => $event->toArray()

        ];


        $jobNotification = new SendNotification($data,MessageType::QuitGroupEvent);
        dispatch($jobNotification);


    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */

    public function subscribe($events)
    {
        $events->listen(
            'App\Events\EventCreated',
            'App\Listeners\EventSubscriber@onEventCreated'
        );
        $events->listen(
            'App\Events\EventConfirmed',
            'App\Listeners\EventSubscriber@onEventConfirmed'
        );
        $events->listen(
            'App\Events\EventUpdated',
            'App\Listeners\EventSubscriber@onEventUpdated'
        );
        $events->listen(
            'App\Events\EventDeleted',
            'App\Listeners\EventSubscriber@onEventDeleted'
        );
        $events->listen(
            'App\Events\EventAccepted',
            'App\Listeners\EventSubscriber@onEventAccepted'
        );
        $events->listen(
            'App\Events\EventQuit',
            'App\Listeners\EventSubscriber@onEventQuit'
        );
    }
}