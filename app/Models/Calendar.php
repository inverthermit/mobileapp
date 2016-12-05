<?php
namespace App\Models;

use App\Traits\CamelCaseAttributes as CamelCaseAttributes;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;

class Calendar extends Model {

    use CamelCaseAttributes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'i_calendar';
    protected $primaryKey = 'calendar_uid';
    public $incrementing = false;
    protected $guarded = [];
    
    /**
     * keep the field in its original name, not changing its style
     * @var array
     */
    protected $camelKeepFields = ['iCalUID'];
    protected $hidden = ['created_at', 'updated_at'];


}
