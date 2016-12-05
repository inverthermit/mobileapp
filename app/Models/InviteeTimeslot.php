<?php
namespace App\Models;

use App\Traits\CamelCaseAttributes as CamelCaseAttributes;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use App\Traits\HasCompositePrimaryKey as HasCompositePrimaryKey;

class InviteeTimeslot extends Model {

    use CamelCaseAttributes;
    use HasCompositePrimaryKey;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'i_invitee_timeslot';
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];
    protected $key = ['invitee_uid', 'timeslot_uid'];



    public static function createIT($timeslotUid, $inviteeUid, $eventUid, $userUid, $status){
        $it = new InviteeTimeslot();
        $it->timeslotUid = $timeslotUid;
        $it->inviteeUid = $inviteeUid;
        $it->eventUid = $eventUid;
        $it->userUid = $userUid;
        $it->status = $status;
        $it->save();
    }
}