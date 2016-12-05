<?php 
namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

use App\Traits\CamelCaseAttributes as CamelCaseAttributes;
use App\Events\ITimeSignupEvent;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract {

    use Authenticatable, CanResetPassword;
    // transform the snake_case to camel_case
    use CamelCaseAttributes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'i_user';
    protected $primaryKey = 'user_uid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['userId', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'rememberToken'];

    /**
     * This mutator automatically hashes the password.
     *
     * @var string
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = \Hash::make($value);
    }

    /**
     * todo: need to check the input is email or phone
     */
    public static function createUnactivatedUser($userId){
        $user = User::where('user_id', $userId)->first();
        if(!empty($user)){
            return $user;
        }
        $user = new User();
        $user->userId = $userId;
        $user->email = $userId;
        $user->status = 'unactivated';
        $user->save();
        event(new ITimeSignupEvent($user));
        return $user;
    }

}
