<?php
namespace app\tebiedemingzi\model;

class DtkGoods extends Base{
    
    /*
     * @param type $num_iid
     * @return type
     */
    public function getGoodsByNumIid($num_iid){
        
        $goodsinfo = $this->where(['num_iid'=>$num_iid])->value("id");
        return $goodsinfo;
    }
}

