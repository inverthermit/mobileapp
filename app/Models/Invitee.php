<?php
namespace App\Models;

use App\Traits\CamelCaseAttributes as CamelCaseAttributes;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;

use App\Traits\HasCompositePrimaryKey as HasCompositePrimaryKey;
use App\Models\InviteeTimeslot;

class Invitee extends Model {

    use CamelCaseAttributes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'i_invitee';
    protected $guarded = ['inviteeTimeslot'];
    protected $hidden = ['delete_level', 'created_at', 'updated_at'];
    public $incrementing = false;
    protected $primaryKey = 'invitee_uid';


    public function inviteeTimeslot(){
        return $this->hasMany('App\Models\InviteeTimeslot', 'invitee_uid', 'invitee_uid');
    }


    /**
     * [diffInvitee description]
     * @param  [array] $inviteeList [description]
     * @param  [string $eventUid    [description]
     * @return [obj]              [description]
     */
    public static function diffInvitee($inviteeList, $eventUid){
        $newInvitees = collect($inviteeList)->map(function($iv){
            return $iv['inviteeUid'];
        });
        $oldInvitees = Invitee::where('event_uid', $eventUid)
                    ->where('delete_level', 0)
                    ->get()->map(function($iv){
                        return $iv->inviteeUid;
                    });
        $oldInvitees = collect($oldInvitees);
        $obj = [];
        $obj[0] = $newInvitees->diff($oldInvitees); // coming invitees;
        $obj[1] = $oldInvitees->diff($newInvitees); // kicked out invitees;
        $obj[2] = $newInvitees->intersect($oldInvitees); // existing invitees
        return $obj;
    }


    /**
     * [clearOutInvitee description]
     * @param  [Array] $outInvitees [the result of function diffInvitee]
     * @param  string eventUid
     * @return [void]              [description]
     */
    public static function clearOutInvitee($outInvitees, $eventUid){
        if(!empty($outInvitees)){
            $userUidList = Invitee::whereIn('invitee_uid', $outInvitees)->get()->map(function($iv){
                return $iv->userUid;
            });
            foreach($outInvitees as $index => $inviteeUid){
                InviteeTimeslot::where('invitee_uid', $inviteeUid)->delete();
                Invitee::where('invitee_uid', $inviteeUid)->update(['delete_level' => 1]);
            }
            // mark their events as deleted
            Event::where('event_uid', $eventUid)
                ->whereIn('user_uid', $userUidList)
                ->update(['status' => 'cancelled', 'delete_level' => 1]);
        }
    }

}
