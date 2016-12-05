<?php
namespace App\Models;

use App\Traits\CamelCaseAttributes as CamelCaseAttributes;
use App\Traits\HasCompositePrimaryKey as HasCompositePrimaryKey;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

use App\Models\Invitee;
use Exception;

class Event extends Model {

    use CamelCaseAttributes;
    use HasCompositePrimaryKey;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'i_event';
    protected $key = ['event_uid', 'calendar_uid'];
    protected $primaryKey = 'event_uid';
    public $incrementing = false;
    protected $guarded = ['photo', 'invitee', 'timeslot'];
    public $camelKeepFields = ['iCalUID'];
    // protected $hidden = ['created_at', 'updated_at'];


    public function invitee(){
        return $this->hasMany('App\Models\Invitee', 'event_uid', 'event_uid');
    }

    public function timeslot(){
        return $this->hasMany('App\Models\Timeslot', 'event_uid', 'event_uid');
    }

    public function photo(){
        return $this->hasMany('App\Models\Photo', 'event_uid', 'event_uid');
    }

    public static function builder($userUid, $calendarUid='', $eventUid='', $deleteLevel = 0){
        $builder = Event::with(['invitee' => function($query){
                        $query->where('delete_level', 0);
                    }, 
                    'invitee.inviteeTimeslot'])
                    ->with(['timeslot' => function($query) use ($userUid){
                        // a closure has to add 'use ($userUid)' to access outer scope
                        $query->join('i_invitee_timeslot', 
                            'i_timeslot.timeslot_uid',
                            '=',
                            'i_invitee_timeslot.timeslot_uid')
                        ->where('i_invitee_timeslot.user_uid', $userUid);
                    }])
                    ->with('photo');
                    // ->where('delete_level', $deleteLevel);
        if ($calendarUid !== ''){
            $builder = $builder->where('calendar_uid', $calendarUid);
        }
        if ($eventUid !== ''){
            $builder = $builder->where('event_uid', $eventUid);
        }
        return $builder;
    }


    /**
     * [updateEvent description]
     * @param  [Event] $event    [description]
     * @param  [string] $eventUid [description]
     * @return [array]           [different key]
     */
    public static function updateEvent($event, $eventUid){
        $diffKey = [];
        $data = [];
        $compareKeys = [
            'summary',
            'location',
            'locationNote',
            'description',
            'url',
            'recurrence',
            'eventType',
            'startTime',
            'endTime',
            'inviteeVisibility'
        ];
        $oldEvent = collect(Event::where('event_uid', $eventUid)->first());
        foreach ($compareKeys as $key) {
            if($event[$key] != $oldEvent[$key]){
                $data[snake_case($key)] = $event[$key];
                array_push($diffKey, $key);
            }
        }
        Event::where('event_uid', $eventUid)->update($data);
        return $diffKey;        
    }

    /**
     * [updateSyncToken description]
     * @param  [type] $eventUid [description]
     * @return [type]           [description]
     */
    public static function updateSyncToken($eventUid){
        Event::where('event_uid', $eventUid)->update(['event_uid' => $eventUid]);
    }

    /**
     * [update the color, bg, icon of a event for all display]
     * @param  [string] $eventUid
     * @param  [string] $userUid
     * @return [void]
     */
    public static function updateDisplay($eventUid, $userUid=''){
        if($userUid == ''){
            $eventList = Event::where('event_uid', $eventUid)->get();
            $inviteeList = Invitee::where('event_uid', $eventUid)->get();
        }else{
            $eventList = Event::where('event_uid', $eventUid)->where('user_uid', $userUid)->get();
            $inviteeList = Invitee::where('event_uid', $eventUid)->where('user_uid', $userUid)->get();
        }
        $inviteeMap = [];
        foreach ($inviteeList as $invitee) {
            $inviteeMap[$invitee->userUid] = $invitee;
        }
        
        foreach ($eventList as $event) {
            $inviteeStatus = 'accepted';
            $isHost = true;
            if(array_key_exists($event->userUid, $inviteeMap)){
                $invitee = $inviteeMap[$event->userUid];
                $inviteeStatus = $invitee->status;
                // todo: may add isHost field in invitee table
                $isHost = $invitee->isHost == 1 ? true : false;
            }
            $eventType = $event->eventType;
            $eventStatus = $event->status;
            $display = Event::encodeDisplay($isHost, $eventType, $eventStatus, $inviteeStatus);

            Event::where('event_uid', $eventUid)
                ->where('user_uid', $invitee->userUid)
                ->update(['display' => $display]);
        }
    }

    public static function encodeDisplay($isHost, $eventType, $eventStatus, $inviteeStatus='accepted'){
        $colors = [
            'solo' => '#06457F',
            'group' => '#63ADF2',
            'public' => '#D8EDFF'
        ];
        $bgs = [
            'pending' => 'slash',
            'updating' => 'slash',
            'confirmed' => 'normal',
            'cancelled' => 'normal',
        ];
        $icons_host = [
            'needsAction' => 'icon_question',
            'accepted' => 'icon_normal',
            'declined' => 'icon_x',
        ];
        $icons_invitee = [
            'needsAction' => 'icon_question',
            'accepted' => 'icon_replied',
            'declined' => 'icon_x',
        ];
        if($isHost){
            return $colors[$eventType].'|'.$bgs[$eventStatus].'|'.$icons_host[$inviteeStatus];
        }else{
            return $colors[$eventType].'|'.$bgs[$eventStatus].'|'.$icons_invitee[$inviteeStatus];
        }
    }

}
