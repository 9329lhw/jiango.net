<?php

namespace app\ios\controller;

use app\ioslogic\UserLogic;
use app\ioslogic\UserPointLogLogic;
use think\Result;
use think\Controller;

/**
 * @author jiang
 */
class User extends Controller {
    
    public function login() {
        $unionid = input('unionid', '');
        if (empty($unionid)) {
            Result::instance()->fail('用户unionid不能为空')->output();
        }

        $userinfo = model('User')->where('unionid', $unionid)->find();

        if (empty($userinfo)) {
            $userinfo['unionid'] = $unionid;
            $userinfo['nickname'] = input('post.nickname', '');
            $userinfo['sex'] = input('post.sex/d', 2);
            $userinfo['headimgurl'] = input('post.headimgurl', '');
            $userinfo = UserLogic::register($userinfo);
            if (!$userinfo) {
                Result::instance()->fail('系统出错，请联系客服')->output();
            }
        }
        $data['uid'] = $userinfo->uid;
        $data['unionid'] = $userinfo->unionid;
        $data['nickname'] = $userinfo->nickname;
        $data['sex'] = $userinfo->sex;
        $data['headimgurl'] = $userinfo->headimgurl;
        $data['api_token'] = UserLogic::update_token($userinfo->uid);
        $data['is_agent'] = empty($userinfo->partner_agent) ? (empty($userinfo->boss_agent) ? 0 : 1) : 1;
        
        $boss = model('UserRelation')->alias('ur')->join('user u','u.uid=ur.boss_agent_uid')->where(['ur.uid'=>$userinfo->uid,'u.boss_agent'=>1])->find();
        $pid = $boss['pid']?$boss['pid']:SHOP_PID;
        session('pid', $pid, 86400);
        session('uid', $userinfo->uid, 86400);

        Result::instance()->data($data)->success('登录成功')->output();
    }

    /**
     * 个人中心
     * @author jiang
     */
    public function center() {
        $uid = input('user_id', 0);
        if (empty($uid)) {
            Result::instance()->fail('用户uid不能为空')->output();
        }
        $data = UserLogic::instance()->center($uid);
        $data['share_rule_url'] = SERVER_PATH.'/shareRule?isApp=1';
        $data['draw_rule_url'] = SERVER_PATH.'/drawRule?isApp=1';
        $data['customer_service'] = CUSTOMER_SERVICE;
        $data['shop_wechat'] = SHOP_WECHAT;
        Result::instance()->data($data)->success('获取成功')->output();
    }

    /**
     * 签到
     * @author jiang
     */
    public function signIn(){
        $uid = input('uid', 0);
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
        $uid = input('uid', 0);
        if (empty($uid)) {
            Result::instance()->fail('用户uid不能为空')->output();
        }
        $page = input('page/d',1);
        $page_size = input('page_size/d',10);
        $type = input('type',2);
        $data = UserLogic::instance()->agentUser($uid,$type,$page,$page_size);
        Result::instance()->data($data)->success('请求成功')->output();
    }
    
    /**
     * 代言人好友列表
     * @author jiang
     */
    public function bossUser() {
        $uid = input('uid', 0);
        if (empty($uid)) {
            Result::instance()->fail('用户uid不能为空')->output();
        }
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
        $uid = input('uid', 0);
        if (empty($uid)) {
            Result::instance()->fail('用户uid不能为空')->output();
        }
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
        $uid = input('uid', 0);
        if (empty($uid)) {
            Result::instance()->fail('用户uid不能为空')->output();
        }
        $where['uid'] = $uid;
        $page = input('page/d',1);
        $page_size = input('page_size/d',20);
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
        Result::instance()->data($data)->success('请求成功')->output();
    }
    
    public function register(){
        $user_name = $this->request->param('user_name');
        $password = $this->request->param('password');
        $user_info = UserLogic::userRegister($user_name,$password);
        
        Result::instance()->success('注册成功',$user_info)->output();
    }
    
     public function checkLogin(){
        $user_name = $this->request->param('user_name');
        $password = $this->request->param('password');
        $user_info = UserLogic::userCheckLogin($user_name,$password);
        
        Result::instance()->success('登录成功',$user_info)->output();
    }

    public function checkVersion(){
//        $app_version = $this->request->param('version_name');
//        $device = $this->request->param('device');
//        if (empty($app_version)) {
//            Result::instance()->errCode(400)->fail('version不能为空')->output();
//        }
//        if (empty($device)) {
//            Result::instance()->errCode(400)->fail('设备号不能为空')->output();
//        }
//        
////        $app_setting = get_device_version($device);
//        if (version_compare($app_version, '1.1.0') == 0) {
//            $data = [
//                'status' => 0
//            ];
//        } else {
            $data = [
                'status' => 0
            ];
//        }
        Result::instance()->success('请求成功', $data)->output();
    }
}
