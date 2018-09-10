<?php

namespace app\admin\model;

class Draws extends Base{
    
    public function getDrawsList($page_size){
        return $this->alias('d')->field('u.nickname,d.*')->join('user u','d.uid=u.uid')->paginate($page_size);
    }
}
