<?php

namespace app\ioslogic;

use think\Base;
use app\common\model\UserPointLog;

class UserPointLogLogic extends Base {
    
    /**
     * 积分明细
     * @author jiang
     */
    public function pointLog($where = array(),$page = 0,$pageSize = 10) {
        $count = model('UserPointLog')->where($where)->count();
        $list = [];
        if($count){
            $list = model('UserPointLog')->where($where)->field('content,point,type,create_time')->order("create_time desc")->limit(($page-1)*$pageSize.",$pageSize")->select()->toArray();
            foreach($list as $k=>$v){
                $list[$k]['content'] = UserPointLog::$pointTypeList[$v['type']];
                $list[$k]['point'] = $v['point']<0?$v['point']:"+".$v['point'];
            }
        }
        $data['count'] = $count;
        $data['list'] = $list;
        $data['total_page'] = $page?ceil($data['count']/$pageSize):1;
        return $data;
    }
}

