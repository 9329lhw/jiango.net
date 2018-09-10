<?php

namespace app\index\controller;

use app\common\model\User;
use app\logic\UserLogic;
use think\Result;

class Index extends \think\Controller {

    public function index() {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == false) {
            echo "<p style='text-align:center'>请在微信中打开！<p>";
        } else {
            if(!session('unionid')){
                if (!empty(input('get.code'))) {
                    $stateStr = SERVER_PATH.urldecode(input('get.state'));
                    $wechatObj = new \wechat\WechatApi();
                    $backOpenidArr = $wechatObj->getOpenid(input('get.code'));
                    if ($backOpenidArr && $backOpenidArr['unionid']) {
                        $user = User::where(['unionid' => $backOpenidArr['unionid']])->find();
                        if (!$user) {
                            $user_info = $wechatObj->getUserInfo($backOpenidArr['openid'],$backOpenidArr['access_token']);
                            $user['unionid'] = $backOpenidArr['unionid'];
                            $user['inviter'] = input('inviter/d',0);
                            $user['nickname'] = $user_info['nickname']?:'';
                            $user['sex'] = $user_info['sex']?:2;
                            $user['headimgurl'] = $user_info['headimgurl']?:'';
                            $user = UserLogic::register($user);
                            if (!$user) {
                                echo "<p style='text-align:center'>系统错误<p>";
                                exit;
                            }
                        } elseif (empty($user->nickname)) {
                            $user_info = $wechatObj->getUserInfo($backOpenidArr['openid']);
                            if ($user_info['subscribe']) {
                                $user->nickname = $user_info['nickname'];
                                $user->sex = $user_info['sex'];
                                $user->headimgurl = $user_info['headimgurl'];
                                $user->save();
                            }
                        }
                        session('openid', $backOpenidArr['openid']);
                        session('unionid', $backOpenidArr['unionid']);
                        session('uid', $user->uid);
                        session('boss_agent',$user->boss_agent);
                        session('partner_agent',$user->partner_agent);
                        $userExtDb = db('UserExtension');
                        $userExt = $userExtDb->where(['uid' => $user->uid, 'extension' => 'openid', 'key' => $wechatObj->getAppid()])->find();
                        if (!$userExt) {
                            $userExtDb->insert(['uid' => $user->uid, 'extension' => 'openid', 'key' => $wechatObj->getAppid(), 'val' => $backOpenidArr['openid']]);
                        }
                        header("Location:" . htmlspecialchars_decode($stateStr));exit;
                    } else {
                        config('app_trace', false);
                        echo "<p style='text-align:center'>系统错误<p>";
                        exit;
                    }
                }else {
                    echo "<p style='text-align:center'>正在努力加载...<p>";
                    $wechatObj = new \wechat\WechatApi();
                    $wechatObj->oauth2Authorize();
                }
            }
            config('app_trace', false);
            return $this->display('index');
        }
    }

    public function redirectAuth() {
        if(session('unionid')){
            $host = "http://" . input('server.SERVER_NAME');
            if (input('server.SERVER_PORT') != "80") {
                $host .= ':' . input('server.SERVER_PORT');
            }
            header("location:" . $host.  urldecode(input('redirect_uri','')) . "");
        }
        if (!empty(input('get.code'))) {
            $stateStr = urldecode(input('get.state'));
            $wechatObj = new \wechat\WechatApi();
            $backOpenidArr = $wechatObj->getOpenid(input('get.code'));
            if ($backOpenidArr) {
                $user = User::where(['unionid' => $backOpenidArr['unionid']])->find();
                if (!$user) {
                    $user_info = $wechatObj->getUserInfo($backOpenidArr['openid']);
                    $user['unionid'] = $backOpenidArr['unionid'];
                    $user['inviter'] = input('inviter/d',0);
                    if ($user_info['subscribe']) {
                        $user['nickname'] = $user_info['nickname'];
                        $user['sex'] = $user_info['sex'];
                        $user['headimgurl'] = $user_info['headimgurl'];
                    }else{
                        $user['nickname'] = '';
                        $user['sex'] = 2;
                        $user['headimgurl'] = '';
                    }
                    $user = UserLogic::register($user);
                    if (!$user) {
                        echo "<p style='text-align:center'>系统错误<p>";
                        exit;
                    }
                    session('openid', $backOpenidArr['openid']);
                    session('unionid', $backOpenidArr['unionid']);
                    session('uid', $user->uid);
                } elseif (empty($user->nickname)) {
                    $user_info = $wechatObj->getUserInfo($backOpenidArr['openid']);
                    if ($user_info['subscribe']) {
                        $user->nickname = $user_info['nickname'];
                        $user->sex = $user_info['sex'];
                        $user->headimgurl = $user_info['headimgurl'];
                        $user->save();
                    }
                }
                $userExtDb = db('UserExtension');
                $userExt = $userExtDb->where(['uid' => $user->uid, 'extension' => 'openid', 'key' => $wechatObj->getAppid()])->find();
                if (!$userExt) {
                    $userExtDb->insert(['uid' => $user->uid, 'extension' => 'openid', 'key' => $wechatObj->getAppid(), 'val' => $backOpenidArr['openid']]);
                }
                header("location:" . htmlspecialchars_decode($stateStr) . "");
            } else {
                config('app_trace', false);
                echo "<p style='text-align:center'>服务器错误<p>";
                exit;
            }
        }else {
            echo "<p style='text-align:center'>正在努力加载...<p>";
            $wechatObj = new \wechat\WechatApi();
            $wechatObj->oauth2Authorize();
        }
    }
    
    public function show(){
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            return $this->index();
        }else{
            return $this->display('index');
        }
    }
    
    public function wxlogin(){
        $wechat = new \wechat\WechatApi('wx5358205ad5acb586','ecefe072fe6b56c9943122e397960df3');
        $jscode = $wechat->getOpenid($_REQUEST['code'],'mini_apps');
        $openid = $jscode['openid'];
        $unionid = $jscode['unionid'];
        
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
        
        $boss = model('UserRelation')->alias('ur')->join('user u','u.uid=ur.boss_agent_uid')->where(['ur.uid'=>$userinfo->uid,'u.boss_agent'=>1])->find();
        $pid = $boss['pid']?$boss['pid']:SHOP_PID;
        session('pid', $pid, 86400);
        session('uid', $userinfo->uid, 86400);
        
        Result::instance()->data($data)->success('登录成功')->output();
    }
    
    public function test(){
        return $this->display();
    }
    
}
