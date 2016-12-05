<?php
namespace App\Models;

use App\Traits\CamelCaseAttributes as CamelCaseAttributes;
use App\Traits\HasCompositePrimaryKey as HasCompositePrimaryKey;

use Illuminate\Database\Eloquent\Model;

class UserCalendar extends Model {

    use CamelCaseAttributes;
    use HasCompositePrimaryKey;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'i_user_calendar';
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];
    protected $key = ['calendar_uid', 'user_uid'];


    /**
     * todo: may change to pivot via hasmany
     * quick way to join a table
     * @return [type] [description]
     */
    public static function builder($userUid='', $calendarUid=''){
        $builder = UserCalendar::join('i_calendar',
                            'i_calendar.calendar_uid',
                            '=',
                            'i_user_calendar.calendar_uid');
        if($userUid !== ''){
            $builder = $builder->where('i_user_calendar.user_uid', $userUid);
        }
        if($calendarUid !== ''){
            $builder = $builder->where('i_user_calendar.calendar_uid', $calendarUid);
        }
        return $builder;
    }

    /**
     * check whether this user has access to calendar
     * @param  [type] $userUid     [description]
     * @param  [type] $calendarUid [description]
     * @return [type]              [description]
     */
    public static function checkAccess($userUid, $calendarUid){
        $validCalendarAccess = UserCalendar::where('calendar_uid', $calendarUid)
                                    ->where('user_uid', $userUid)
                                    ->exists();
        return $validCalendarAccess;
    }

}