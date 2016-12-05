<?php
namespace App\Models;

use App\Traits\CamelCaseAttributes as CamelCaseAttributes;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;

use App\Models\Event;
use App\Models\InviteeTimeslot;

class Timeslot extends Model {

    use CamelCaseAttributes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'i_timeslot';
    protected $guarded = ['inviteeUid','userUid', 'status', 'rate'];
    protected $hidden = ['created_at', 'updated_at'];

    protected $primaryKey = 'timeslot_uid';

    public $incrementing = false;


    /**
     * update the statistic of invitees
     * @param  [type] $eventUid [description]
     * @return [type]           [description]
     */
    public static function updateInviteeNum($eventUid){
        $timeslots = Timeslot::where('event_uid', $eventUid)
                            ->select('timeslot_uid')->get()
                            ->map(function($t){
                                return $t->timeslotUid;
                            });
        foreach ($timeslots as $timeslotUid) {
            $acceptedNum = InviteeTimeslot::where('event_uid', $eventUid)
                            ->where('timeslot_uid', $timeslotUid)
                            ->where('status', 'accepted')->count();
            $rejectedNum = InviteeTimeslot::where('event_uid', $eventUid)
                            ->where('timeslot_uid', $timeslotUid)
                            ->where('status', 'rejected')->count();
            $pendingNum = InviteeTimeslot::where('event_uid', $eventUid)
                            ->where('timeslot_uid', $timeslotUid)
                            ->where('status', 'pending')->count();
            $totalNum = Timeslot::where('event_uid', $eventUid)->count();
            Timeslot::where('timeslot_uid', $timeslotUid)->update(['accepted_num' => $acceptedNum]);
            Timeslot::where('timeslot_uid', $timeslotUid)->update(['rejected_num' => $rejectedNum]);
            Timeslot::where('timeslot_uid', $timeslotUid)->update(['pending_num' => $pendingNum]);
            Timeslot::where('timeslot_uid', $timeslotUid)->update(['total_num' => $totalNum]);
        }
    }


    public static function diffTimeslot($timeslotList, $eventUid){
        $newTimeslots = collect($timeslotList)->map(function($iv){
            return $iv['timeslotUid'];
        });
        $oldTimeslots = Timeslot::where('event_uid', $eventUid)->get()->map(function($iv){
            return $iv->timeslotUid;
        });
        $oldTimeslots = collect($oldTimeslots);
        $obj = [];
        $obj[0] = $newTimeslots->diff($oldTimeslots); // coming timeslots
        $obj[1] = $oldTimeslots->diff($newTimeslots); // kicked out timeslots
        return $obj;
    }

    /**
     * [clearOutTimeslot description]
     * @param  [array] $outTimeslots [the result of diffTimeslot]
     * @param  [String] $eventUid
     * @return [type]               [description]
     */
    public static function clearOutTimeslot($outTimeslots, $eventUid){
        if(!empty($outTimeslots)){
            // todo: remove it to current event: (userUid, eventUid)
            foreach($outTimeslots as $index => $timeslotUid){
                InviteeTimeslot::where('timeslot_uid', $timeslotUid)->delete();
                Timeslot::where('timeslot_uid', $timeslotUid)->delete();
            }
        }
    }






}