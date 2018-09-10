<?php
namespace app\common\model;

class GoodsTbPwd extends \think\Model{
    
    /*
     * @param type $num_iid
     * @return type
     */
    public function getGoodsTbPwd($num_iid,$pid){
        return GoodsTbPwd::field('tbpwd,coupon_id,url')->where(['num_iid' => $num_iid, 'pid' => $pid])->find();
    }
   
}

