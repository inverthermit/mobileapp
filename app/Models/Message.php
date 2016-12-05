<?php

namespace App\Models;

use App\Traits\CamelCaseAttributes as CamelCaseAttributes;
use App\Traits\HasCompositePrimaryKey as HasCompositePrimaryKey;


use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class Message extends Model {

    use CamelCaseAttributes;

    protected $table = 'i_message';
    protected $primaryKey = 'message_uid';
    protected $guarded = [];
    // protected $hidden = ['created_at', 'updated_at'];
    public $camelKeepFields = [];

    protected $casts = [
        'has_badge' => 'boolean',
        'is_read' => 'boolean'
    ];


   

}