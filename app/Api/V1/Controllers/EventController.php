<?php

namespace App\Api\V1\Controllers;

use App\Models\Event;
use App\Models\User;
use App\Models\Invitee;
use App\Models\Timeslot;
use App\Models\InviteeTimeslot;
use App\Models\Photo;
use App\Models\UserCalendar;

use App\Lib\Output;
use App\Lib\Helper;

use App\Events\EventCreated;
use App\Events\EventConfirmed;
use App\Events\EventUpdated;
use App\Events\EventDeleted;
use App\Events\EventAccepted;
use App\Events\EventQuit;

use App\Jobs\SendNotification;
use App\Jobs\SendReminderEmail;

use JWTAuth;
use Validator;
use Config;
use Log;
use Uuid;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\Password;
use Tymon\JWTAuth\Exceptions\JWTException;
use Dingo\Api\Exception\ValidationHttpException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EventController extends Controller
{

    /**
     * [insert description]
     * @param  Request $request [description]
     * @return [Output<Event>]           [description]
     */
    public function insert(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $event = $request->json()->all();
        $output = new Output();

        // todo: need to be deleted
        unset($event['isAllDay']);
        try{
            $inviteeList = $event['invitee'];
            $timeslotList = $event['timeslot'];
            $photoList  = $event['photo'];
            $eventUid = $event['eventUid'];
            $calendarUid = $event['calendarUid'];

            if(!UserCalendar::checkAccess($user->userUid, $calendarUid)){
                throw new Exception('bad calendar access');
            }

            // todo: here we need to check the friendship
            foreach ($inviteeList as &$invitee) {
                if(!isset($invitee['inviteeUid'])){
                    $invitee['inviteeUid'] = Uuid::generate()->string;
                }
                $userUid = $invitee['userUid'];
                if($userUid < 0){
                    $userUid = User::createUnactivatedUser($invitee['userId'])->userUid;
                    $invitee['userUid'] = $userUid;
                }
                // create new event invitation for each invitee
                $event['userUid'] = $userUid;
                $event['hostUserUid'] = $user->userUid;
                if($userUid == $user->userUid){
                    $event['userUid'] = $user->userUid;
                    $event['calendarUid'] = $calendarUid;
                }else{
                    $event['userUid'] = $userUid;
                    $event['calendarUid'] = $userUid;
                }
                Event::create($event);

                // create invitee
                $invitee['eventUid'] = $eventUid;
                if($invitee['userUid'] == $user->userUid){
                    $invitee['status'] = 'accepted';
                }else{
                    $invitee['status'] = 'needsAction';
                }
                Invitee::create($invitee);
            }
            // use unset to remove the reference of foreach, othewise, it remains the last item
            unset($invitee);

            // 1. insert timeslot
            foreach ($timeslotList as $timeslot) {
                $timeslot['eventUid'] = $eventUid;
                Timeslot::create($timeslot);
            }

            // 2. insert invitee and their response
            foreach ($inviteeList as $invitee) {
                foreach($timeslotList as $timeslot){
                    $it = new InviteeTimeslot();
                    $it->timeslotUid = $timeslot['timeslotUid'];
                    $it->inviteeUid = $invitee['inviteeUid'];
                    $it->eventUid = $eventUid;
                    $it->userUid = $invitee['userUid'];
                    if($invitee['userUid'] == $user->userUid){
                        $it->status = 'accepted';
                    }else{
                        $it->status = 'pending';
                    }
                    $it->save();
                }
            }

            // 3. insert photos
            foreach ($photoList as $photo ) {
                $photo['eventUid'] = $eventUid;
                Photo::create($photo);
            }

            // 4. update timeslot statistics information
            Timeslot::updateInviteeNum($eventUid);
            Event::updateDisplay($eventUid);

            $event = Event::builder($user->userUid, $calendarUid, $eventUid)->first();
            if(!empty($event)){
                $output->data = $event;
                $output->info = trans('message.data_success');
                $output->status = 1;
                // todo: need to call back on event created
                 event(new EventCreated($event));
            }else{
                $output->info = trans('message.data_failed');
                $output->status = -2;
            }
        } catch (Exception $e){
            // throw $e;
            // var_dump($e->getMessage());
            // $output->info = trans('message.data_failed');
            $output->info = $e->getMessage();
            $output->status = -3;

            info($e);
        }

        return response()->json($output);
    }



    /**
     * method: get
     * input: (mandatory)
     *     clendarUid
     * input: (optional)
     *     timeMin: timestamp, the lower bound of query
     *     timeMax: timestamp, the upper bound of query 
     *     syncToken: string, generated by urlencode(updated_at) function
     * @param  Request $request
     * @param  [string]  $calendarUid
     * @return [Output<Event>]
     */
    public function lists(Request $request, $calendarUid){
        $user = JWTAuth::parseToken()->authenticate();
        $builder = Event::builder($user->userUid, $calendarUid);

        // $timeMin = $request->input('timeMin');
        // $timeMax = $request->input('timeMax');
        // $builder = $builder->where('start_time', '>', $timeMin);
        // $builder = $builder->where('end_time', '<', $timeMax);

        $syncToken = urldecode($request->input('syncToken'));
        // $syncToken = '2016-10-31 07:01:12';
        // $syncToken = '2016-10-31 07:01:54';
        if($syncToken != ''){
            $builder = $builder->where('updated_at', '>', $syncToken);
        }
        $builder = $builder->orderBy('updated_at', 'desc');
        $eventList = $builder->get(); 

        if(count($eventList) > 0){
            $syncToken = urlencode($eventList[0]->updatedAt->toDateTimeString());
        }
        $output = new Output();
        $output->data = $eventList;
        $output->syncToken = $syncToken;
        $output->info = trans('message.data_success');
        $output->status = 1;
        return response()->json($output);
    }

    /**
     * [get the event for login user]
     * note: if calendar sharing is added, need to consider current user and hosted calendar user
     * @param  [String]  $calendarUid [-1 for ignoring calendarUid, otherwise consider it into query]
     * @param  [String]  $eventUid    
     * @return [Output<Event>]        
     */
    public function get(Request $request, $calendarUid, $eventUid){
        $user = JWTAuth::parseToken()->authenticate();
        $calendarUid = $calendarUid == '-1' ? '' : $clendarUid;
        $event = Event::builder($user->userUid, $calendarUid, $eventUid)->first();
        $output = new Output();
        if(!empty($event)){
            $output->data = $event;
            $output->info = trans('message.data_success');
            $output->status = 1;
        }else{
            $output->info = trans('message.data_failed');
            $output->status = -1;
        }
        return response()->json($output);
    }

    public function update(Request $request, $calendarUid, $eventUid){
        $user = JWTAuth::parseToken()->authenticate();
        $event = $request->json()->all();
        $output = new Output();

        try{
            $inviteeList = $event['invitee'];
            $timeslotList = $event['timeslot'];
            $photoList  = $event['photo'];
            $eventUid = $event['eventUid'];
            $calendarUid = $event['calendarUid'];
            unset($event['createdAt']);
            unset($event['updatedAt']);

            if(!UserCalendar::checkAccess($user->userUid, $calendarUid)){
                throw new Exception('bad calendar access');
            }

            // 1. check whether there are new invitees
            list($inInvitees, $outInvitees, $curInvitees) = Invitee::diffInvitee($inviteeList, $eventUid);

            // 2. update for existing invitees and create for coming invitees
            $updatedFields = Event::updateEvent($event, $eventUid);
            // remove the last kicked-out invitee
            Invitee::where('event_uid', $eventUid)->where('delete_level', 1)->delete();
            // updating from solo event to group event, new invitee > 0 and old invitee==0
            if(count($inviteeList) > 0 && Invitee::where('event_uid', $eventUid)->where('delete_level', 0)->count() == 0){
                $event['status'] = 'pending';
            }
            foreach ($inviteeList as &$invitee) {
                $userUid = $invitee['userUid'];
                if($userUid < 0){
                    $userUid = User::createUnactivatedUser($invitee['userId'])->userUid;
                    $invitee['userUid'] = $userUid;
                }

                // create new event invitation or update event
                $event['userUid'] = $userUid;
                $event['hostUserUid'] = $user->userUid;
                if($inInvitees->contains($invitee['inviteeUid'])){
                    $event['userUid'] = $userUid;
                    $event['calendarUid'] = $userUid;
                    Event::create($event);
                }

                // add new invitees
                if($inInvitees->contains($invitee['inviteeUid'])){
                    $invitee['eventUid'] = $eventUid;
                    $invitee['status'] = 'needsAction';
                    Invitee::create($invitee);
                }
            }

            // return response()->json($inviteeList);
            // use unset to remove the reference of foreach, othewise, it remains the last item
            unset($invitee);

            // 3. check whether there are new timeslots
            list($inTimeslots, $outTimeslots) = Timeslot::diffTimeslot($timeslotList, $eventUid);

            // 4. insert new timeslot
            foreach ($timeslotList as $timeslot) {
                if($inTimeslots->contains($timeslot['timeslotUid'])){
                    $timeslot['eventUid'] = $eventUid;
                    Timeslot::create($timeslot);
                }
            }
            
            // 3. insert invitee and their response
            foreach ($inviteeList as $invitee) {
                foreach($timeslotList as $timeslot){
                    // if there are new invitees or new timeslots
                    // create a response in InviteeTimeslot table
                    if($inInvitees->contains($invitee['inviteeUid']) 
                        || $inTimeslots->contains($timeslot['timeslotUid'])){
                        $it = new InviteeTimeslot();
                        $it->timeslotUid = $timeslot['timeslotUid'];
                        $it->inviteeUid = $invitee['inviteeUid'];
                        $it->eventUid = $eventUid;
                        $it->userUid = $invitee['userUid'];
                        if($invitee['userUid'] == $user->userUid){
                            $it->status = 'accepted';
                        }else{
                            $it->status = 'pending';
                        }
                        $it->save();
                    }
                }
            }

            // 4. insert photos
            Photo::where('event_uid', $eventUid)->delete();
            foreach ($photoList as $photo ) {
                $photo['eventUid'] = $eventUid;
                Photo::create($photo);
            }

            if(count($inTimeslots) != 0 || count($outTimeslots) != 0){
                array_unshift($updatedFields, 'timeslot');
                // todo: how about cancelled event
                Event::where('event_uid', $eventUid)
                    ->where('status', 'confirmed')
                    ->update(['status' => 'updating']);

                Invitee::where('event_uid', $eventUid)
                    ->update(['status' => 'needsAction']);
            }
            // 5. delete kicked out invitees
            Invitee::clearOutInvitee($outInvitees, $eventUid);
            // 6. delete kicked out timeslots
            Timeslot::clearOutTimeslot($outTimeslots, $eventUid);

             // update from group event to solo event, need to cancelled other invitee's event
            if(Invitee::where('event_uid', $eventUid)->where('delete_level', 0)->count() == 0){
                Event::where('event_uid', $eventUid)
                    ->where('user_uid', $user->userUid)
                    ->update(['status' => 'confirmed']);
            }

            // 7. update timeslot statistic information
            Timeslot::updateInviteeNum($eventUid);
            Event::updateDisplay($eventUid);           
             
            $event = Event::builder($user->userUid, $calendarUid, $eventUid)->first();
            if(!empty($event)){
                $output->data = $event;
                $output->info = trans('message.data_success');
                $output->status = 1;
                
                // 8. fire event
                event(new EventUpdated($event, $inInvitees, $outInvitees, $curInvitees, $updatedFields));
            }else{
                $output->info = trans('message.data_failed');
                $output->status = -2;
            }

        } catch (Exception $e){
            // $output->info = trans('message.data_failed');
            $output->info = $e->getMessage();
            $output->status = -3;
            info($e);
        }

        return response()->json($output);
    
    }

    /**
     * [delete description]
     * method: post
     * 
     * @param  Request $request     [description]
     * @param  [String]  $calendarUid [description]
     * @param  [String]  $eventUid    [description]
     * @return [Void]               [description]
     */
    public function delete(Request $request, $calendarUid, $eventUid){
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        try{
            Event::where('event_uid', $eventUid)->update(['delete_level' => 1, 'status' => 'cancelled']);
            // todo: need to check whether this event has invitees
            // fire on event delete

            $event = Event::builder($user->userUid, $calendarUid, $eventUid, 1)->first();
            event(new EventDeleted($event));

            $output->data = $event;
            $output->info = trans('message.data_success');
            $output->status = 1;
        } catch (Exception $e){
            $output->data = $e->getMessage();
            $output->info = trans('message.data_failed');
            $output->status = -1;
        }
        return response()->json($output);
    }


    /**
     * [confirm description]
     * @param  Request $request     [description]
     * @param  [String]  $calendarUid [description]
     * @param  [String]  $eventUid    [description]
     * @param  [String]  $timeslotUid [description]
     * @return [Output<Event>]               [description]
     */
    public function confirm(Request $request, $calendarUid, $eventUid, $timeslotUid){
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        try{
            // todo: need to check whether this event has already been confirmed
            $timeslot = Timeslot::where('timeslot_uid', $timeslotUid)->first();
            $startTime = $timeslot->startTime;
            $endTime = $timeslot->endTime;

            $event = Event::where('event_uid', $eventUid)
                        ->where('status', '!=', 'cancelled')
                        ->update([
                            'status' => 'confirmed',
                            'start_time' => $startTime,
                            'end_time' => $endTime
                        ]);
            $timeslot->isConfirmed = 1;
            $timeslot->save();
            // 0. select invitees who do not agree this timeslot
            $notAgreedInvitees = InviteeTimeslot::where('timeslot_uid', $timeslotUid)
                                    ->where('event_uid', $eventUid)
                                    ->where('status', '!=', 'accepted')
                                    ->select('invitee_uid')
                                    ->get()
                                    ->map(function($t){
                                        return $t->inviteeUid;
                                    });
            // 1. select the invitees who have accepted some timeslots
            $acceptedInvitees = Invitee::where('event_uid', $eventUid)
                                    ->where('delete_level', 0)
                                    ->where('status', 'accepted')
                                    ->select('invitee_uid')
                                    ->get()
                                    ->map(function($t){
                                        return $t->inviteeUid;
                                    });
            // do interact operation: accepted time != confirmed time
            $resetInviteeUids = collect($notAgreedInvitees)->intersect($acceptedInvitees);

            // 2. set invitee's timeslot status with 'pending'
            InviteeTimeslot::whereIn('invitee_uid', $resetInviteeUids)
                ->update(['status' => 'pending']);

            // 3. set invitee's response status with 'needsAction' 
            Invitee::whereIn('invitee_uid', $resetInviteeUids)
                ->update(['status' => 'needsAction']);

            // 4. update the acceptedNum of timeslot
            Timeslot::updateInviteeNum($eventUid);
            Event::updateDisplay($eventUid);

            $event = Event::builder($user->userUid, $calendarUid, $eventUid)->first();
            if(!empty($event)){
                $output->data = $event;
                $output->info = trans('message.data_success');
                $output->status = 1;

                event(new EventConfirmed($event, $resetInviteeUids));
            }else{
                $output->info = trans('message.data_failed');
                $output->status = -1;
            }
        } catch (Exception $e){
            // var_dump($e->getMessage());
            $output->data = $e->getMessage();
            $output->info = trans('message.data_failed');
            $output->status = -2;
        }
        return response()->json($output);
    }

    /**
     * method: post
     * form:
     *     Event event
     *     List<Contact> contact
     * @param  Request $request 
     * @return Output<Void>
     */
    public function forward(Request $request){
        $output = new Output();
        $output->info = trans('message.data_failed');
        $output->status = -1;
        return response()->json($output);
    }

    /**
     * [acceptEvent description]
     * @param  Request $request  [description]
     * @param  [String]  $calendarUid [description]
     * @param  [String]  $eventUid [description]
     * @return [Output<Event>]            [description]
     */
    public function acceptEvent(Request $request, $calendarUid, $eventUid){
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        try{
            // 1. set invitee's response status with 'accepted' 
            Invitee::where('event_uid', $eventUid)
                ->where('delete_level', 0)
                ->where('user_uid', $user->userUid)
                ->update(['status' => 'accepted']);

            // 2. set invitee's event status with 'confirm'
            Event::where('calendar_uid', $calendarUid)
                ->where('event_uid', $eventUid)
                ->update(['status' => 'confirmed']);

            $event = Event::builder($user->userUid, $calendarUid, $eventUid)->first();
            event(new EventAccepted($event));

            Event::updateSyncToken($eventUid);
            $output->data = $event;
            $output->info = trans('message.data_success');
            $output->status = 1;
        } catch (Exception $e){
            $output->data = $e->getMessage();
            $output->info = trans('message.data_failed');
            $output->status = -1;
        }
        return response()->json($output);
    }

    /**
     * [quitEvent description]
     * method: post
     * 
     * @param  Request $request  [description]
     * @param  [String]  $calendarUid [description]
     * @param  [String]  $eventUid [description]
     * @return [Output<Event>]            [description]
     */
    public function quitEvent(Request $request, $calendarUid, $eventUid){
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        try{
            // 1. set invitee's response status with 'declined' 
            Invitee::where('event_uid', $eventUid)
                ->where('delete_level', 0) 
                ->where('user_uid', $user->userUid)
                ->update(['status' => 'declined']);

            // 2. set invitee's event status with 'cancelled'
            Event::where('calendar_uid', $calendarUid)
                ->where('event_uid', $eventUid)
                ->update(['status' => 'cancelled']);

            $event = Event::builder($user->userUid, $calendarUid, $eventUid)->first();
            event(new EventQuit($event));

            Event::updateSyncToken($eventUid);
            $output->data = $event;
            $output->info = trans('message.data_success');
            $output->status = 1;
        } catch (Exception $e){
            $output->info = trans('message.data_failed');
            $output->status = -1;
        }
        return response()->json($output);
    
    }

    /**
     * method: post
     * form:
     *     [List<String>] timeslots [timeslotUid array]
     * @param  Request $request [description]
     * @param  [String]  $calendarUid [description]
     * @param  [String]  $eventUid [description]
     * @return [Output<Event>]           [description]
     */
    public function acceptTimeslots(Request $request, $calendarUid, $eventUid){
        $user = JWTAuth::parseToken()->authenticate();
        // todo: need to check whether timeslot is empty
        $timeslots = $request->input('timeslots');
        $output = new Output();
        if(empty($timeslots)){
            $output->info = 'timeslot cannot be empty';
            $output->status = -1;
            return response()->json($output);
        }
        try{
            // 1. set the selected timeslots' status with 'accepted'
            // and unselected timeslots' status with 'rejected'
            InviteeTimeslot::where('user_uid', $user->userUid)
                ->where('event_uid', $eventUid)
                ->whereNotIn('timeslot_uid', $timeslots)
                ->update(['status' => 'rejected']);
            InviteeTimeslot::where('user_uid', $user->userUid)
                ->where('event_uid', $eventUid)
                ->whereIn('timeslot_uid', $timeslots)
                ->update(['status' => 'accepted']);

            // 2. set invitee's response status with 'accepted' 
            Invitee::where('event_uid', $eventUid)  
                ->where('delete_level', 0)
                ->where('user_uid', $user->userUid)
                ->update(['status' => 'accepted']);

            Timeslot::updateInviteeNum($eventUid);
            Event::updateDisplay($eventUid, $user->userUid);

            // 3. auto confirm when 2 people meeting
            $needsAutoConfirm = false;
            if(Invitee::where('event_uid', $eventUid)->where('is_host', 0)->count() == 1){
                $needsAutoConfirm = true;
                Event::where('event_uid', $eventUid)->update(['status' => 'confirmed']);
            }

            // 4. update the acceptedNum of timeslot
            $event = Event::builder($user->userUid, $calendarUid, $eventUid)->first();
            event(new EventAccepted($event));
            if($needsAutoConfirm){
                event(new EventConfirmed($event));
            }

            Event::updateSyncToken($eventUid);
            $output->data = $event;
            $output->info = trans('message.data_success');
            $output->status = 1;
        } catch (Exception $e){
            $output->info = trans('message.data_failed');
            $output->status = -2;
        }
        return response()->json($output);
    }

    /**
     * [rejectTimeslots description]
     * method: post
     * form:
     *     String reason [why invitee reject all timeslots]
     * 
     * @param  Request $request [description]
     * @param  [String]  $calendarUid [description]
     * @param  [String]  $eventUid [description]
     * @return [type]           [description]
     */
    public function rejectTimeslots(Request $request, $calendarUid, $eventUid){
        $user = JWTAuth::parseToken()->authenticate();
        $output = new Output();
        try{
            // 1. set the timeslot's status with 'rejected'
            InviteeTimeslot::where('user_uid', $user->userUid)
                ->where('event_uid', $eventUid)
                ->update(['status' => 'rejected']);

            // 2. set invitee's response status with 'declined'
            $reason = $request->input('reason');
            Invitee::where('event_uid', $eventUid)
                ->where('delete_level', 0)
                ->where('user_uid', $user->userUid)
                ->update(['status' => 'declined', 'reason' => $reason]);

            // 3. set invitee's event status with 'cancelled'
            Event::where('calendar_uid', $calendarUid)
                ->where('event_uid', $eventUid)
                ->update(['status' => 'cancelled']);

            // 4. update the acceptedNum of timeslot
            Timeslot::updateInviteeNum($eventUid);
            Event::updateDisplay($eventUid, $user->userUid);

            $event = Event::builder($user->userUid, $calendarUid, $eventUid)->first();
            event(new EventQuit($event));

            Event::updateSyncToken($eventUid);
            $output->data = $event;
            $output->info = trans('message.data_success');
            $output->status = 1;
        } catch (Exception $e){
            $output->data = $e->getMessage();
            $output->info = trans('message.data_failed');
            $output->status = -1;
        }
        return response()->json($output);
    
    }


    /**
     * recommend timeslots
     * method: post
     * form:
     *     invitee: [1, 2, 3], the ids of selected invitees
     *     startRecommendTime: in millisecond, the start time of recommendation
     * @param  Request $request 
     * @return [Timeslot]
     */
    public function recommendTimeslots(Request $request){
        $currentTime = round(microtime(true) * 1000);
        $startRecommendTime = $request->input('startRecommendTime');
        $inviteeList = $request->input('invitee');
        if(!empty($startRecommendTime)){
            $currentTime = $startRecommendTime;
        }

        $totalSlots = 3;
        $duration = 1;
        $slotArr = [];
        for($i = 0; $i < $totalSlots; $i++){
            $slot = new Timeslot();
            $slot->timeslotUid = Uuid::generate()->string;
            $slot->startTime = $currentTime + $i * 3600 * 1000;
            $slot->endTime = $currentTime + ($i + 1) * 3600 * 1000;
            $slot->isSystemSuggested = 1;
            array_push($slotArr, $slot);
        }

        $output = new Output();
        $output->data = $slotArr;
        $output->info = trans('message.data_success');
        $output->status = 1;

        return response()->json($output);
    }


    /**
     * method: post
     * form: 
     *     String photoUid
     *     File photo
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function uploadPhoto(Request $request){
        $photoUid = $request->input('photoUid');
        if(empty($photoUid)){
            $photoUid = Uuid::generate(4)->string;
        }
        $output = new Output();
        if(!$request->hasFile('photo')){
            $output->info = 'no photo upload';
            $output->status = -1;
        }
        $file = $request->file('photo');
        $destpath = public_path('upload');
        $newFilename = $photoUid.'.'.$file->guessExtension();
        if(!$file->isValid() || !isImage($file->getMimeType())){
            $output->info = 'invalid image type';
            $output->status = -2;
        }
        try{
            $file->move($destpath, $newFilename);
            $obj = (object)[];
            $obj->photoUid = $photoUid;
            $obj->url = url('/').'/upload/'.$newFilename;
            $output->data = $obj;
            $output->info = trans('message.data_success');
            $output->status = 1;
        } catch(Exception $e) {
            $output->info = 'upload error';
            $output->status = -3;
        }
        return response()->json($output);
    }

    /**
     * method: post
     * form:
     *     String url
     * 
     * @param  Request $request     [description]
     * @param  [string]  $calendarUid [description]
     * @param  [string]  $eventUid    [description]
     * @param  [string]  $photoUid    [description]
     * @return [Output<Event>]
     */
    public function updatePhoto(Request $request, $calendarUid, $eventUid, $photoUid){
        $url = $request->input('url');
        $output = new Output();
        if($url == ''){
            $output->info = 'url canno be null';
            $output->status = -1;
        }
        $user = JWTAuth::parseToken()->authenticate();
        try {
            Photo::where('event_uid', $eventUid)
                    ->where('photo_uid', $photoUid)
                    ->update(['url' => $url, 'success' => 1]);
            $event = Event::builder($user->userUid, $calendarUid, $eventUid)->first();
            if(!empty($event)){
                $output->data = $event;
                $output->info = trans('message.data_success');
                $output->status = 1;
            }else{
                $output->info = trans('message.data_failed');
                $output->status = -2;
            }
        } catch (Exception $e) {
            $output->data = $e->getMessage();
            $output->info = trans('message.data_failed');
            $output->status = -3;
        }
        return response()->json($output);
    }



    public function test(){
        // return 
    }


}

