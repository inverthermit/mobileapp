<?php

namespace App\Models;

use App\Traits\CamelCaseAttributes as CamelCaseAttributes;
use App\Traits\HasCompositePrimaryKey as HasCompositePrimaryKey;


use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model {

    use CamelCaseAttributes;

    protected $table = 'i_photo';
    protected $primaryKey = 'photo_uid';
    public $incrementing = false;
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];

}