<?php

namespace app\index\controller;

use think\Controller;

class Error extends Controller{
    
    public function index(){
        return $this->fetch('../../404');
    }
}
