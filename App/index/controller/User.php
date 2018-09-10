<?php

namespace app\index\controller;

use app\logic\UserLogic;
use app\logic\UserPointLogLogic;
use app\common\model\UserPointLog;
use think\Result;


/**
 * @author jiang
 */
class User extends Base {

    /**
     * 个人中心
     * @author jiang
     */
    public function center() {
        $uid = $this->uid;
        $data = UserLogic::instance()->center($uid);
        $data['customer_service'] = CUSTOMER_SERVICE;
        $data['shop_wechat'] = SHOP_WECHAT;
        $data['wechat_share'] = $this->wechatShare;
        Result::instance()->data($data)->success('获取成功')->output();
    }
    
    /**
     * 签到
     * @author jiang
     */
    public function signIn(){
        $uid = $this->uid;
        $result = UserLogic::instance()->signIn($uid);
        if($result === true){
            Result::instance()->success('签到成功，获得'.\lib\cache\CacheTool::configsCache('sign_in_point').'积分')->output();
        }else{
            Result::instance()->fail($result)->output();
        }
    }

    /**
     * 好友列表
     * @author jiang
     */
    public function agentUser() {
        $uid = $this->uid;
        $page = input('page/d',1);
        $page_size = input('page_size/d',10);
        $type = input('type',2);
        $data = UserLogic::instance()->agentUser($uid,$type,$page,$page_size);
        $data['wechat_share'] = $this->wechatShare;
        Result::instance()->data($data)->success('请求成功')->output();
    }
    
    /**
     * 代言人好友列表
     * @author jiang
     */
    public function bossUser() {
        $uid = $this->uid;
        $page = input('page/d',1);
        $page_size = input('page_size/d',10);
        $data = UserLogic::instance()->bossUser($uid,$page,$page_size);
        Result::instance()->data($data)->success('请求成功')->output();
    }

    /**
     * 合伙人好友列表
     * @author jiang
     */
    public function partnerUser() {
        $uid = $this->uid;
        $type = input('type','boss');
        $page = input('page/d',1);
        $page_size = input('page_size/d',10);
        $data = UserLogic::instance()->partnerUser($uid,$type,$page,$page_size);
        Result::instance()->data($data)->success('请求成功')->output();
    }
    
    /**
     * 积分明细
     * @author jiang
     */
    public function pointLog(){
        $where['uid'] = $this->uid;
        $page = input('page/d',1);
        $page_size = input('page_size/d',10);
        if(is_string(input('type'))){
            $where['type'] = input('type');
        }
        if(is_numeric(input('inc_dec'))){
            if(input('inc_dec')){
                $where['point'] = ['gt',0];
            }else{
                $where['point'] = ['lt',0];
            }
        }
        $data = UserPointLogLogic::instance()->pointLog($where,$page,$page_size);
        $data['customer_service'] = CUSTOMER_SERVICE;
        $data['wechat_share'] = $this->wechatShare;
        Result::instance()->data($data)->success('请求成功')->output();
    }

}
