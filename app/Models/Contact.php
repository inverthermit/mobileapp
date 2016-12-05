<?php
namespace App\Models;

use App\Traits\CamelCaseAttributes as CamelCaseAttributes;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;

class Contact extends Model {

    use CamelCaseAttributes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'i_contact';

    public static function createModels(array $models = [])
    {

        foreach($models as $model){

            $model = new static($model);
            $model->save();

        }

        return true;
    }



}
