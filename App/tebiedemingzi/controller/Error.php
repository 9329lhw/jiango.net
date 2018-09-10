<?php

namespace app\tebiedemingzi\controller;

use think\Controller;

class Error extends Controller{
    
    public function index(){
        return $this->fetch('../../404');
    }
}
