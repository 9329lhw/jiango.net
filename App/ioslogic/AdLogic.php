<?php
namespace app\ioslogic;
use think\Base;
use think\Result;
use app\common\model\Ad;

class AdLogic extends Base {
    
    public function getAdList() {
        $list = Ad::getAdList();
        foreach ($list as $key => $res){
            $list[$key]['extimg'] = IMG_PATH.$res['extimg'];
        }
        return $list;
    }
    
    public function getAdInfo($ad_id){
        if(empty($ad_id)){
            Result::instance()->fail('banner_id不能为空')->output();
        }
        $info = Ad::getAdInfoById($ad_id);
        if(empty($info)){
            Result::instance()->fail('banner不存在')->output();
        }
        $info['extimg'] = IMG_PATH.$info['extimg'];
        return $info;
    }

}

