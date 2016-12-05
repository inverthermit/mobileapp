<?php
namespace App\Lib;

/**
 * the data structure for returning json
 */
class Output {
    public $data = [];
    public $info = 'success';
    public $status = 1;
    public $syncToken = '';

    public function __construct(){
        $holderCls = new \stdClass();
        $holderCls->__server__placeholder = 'placeholder';
        $this->data = $holderCls;
    }
}

