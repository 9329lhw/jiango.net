<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Result;

class Base extends Controller{
    protected $uid;
    protected $pid;
    protected $partner_agent;
    protected $boss_agent;
    public $pageSize = 20; //分页数
    public $newPageSize = 10; //分页数
    protected $weixinObj; //微信对象
    public $wechatShare = array();
    
    public function __construct(Request $request) {
        header('Access-Control-Allow-Origin:*');
        //调试模式
        if(input('debug')){
            session('uid',input('debug'));
        }
//        session('uid',33);
        $this->pid = session('pid');
        $this->uid = session('uid');
        $this->uid = 37;
        $this->partner_agent = session('partner_agent');
        $this->boss_agent = session('boss_agent');
        if(!in_array($request->controller().'_'.$request->action(),array('Prize_detail'))){
            if(empty($this->uid)){
                Result::instance()->errCode(401)->fail('请登录')->output();
            }
        }
        $logintime = session('pidtime');
        if(!$logintime || $logintime<(time()-3600) || empty($this->pid)){
            $user = model('User')->where('uid',$this->uid)->find();
            if($user->boss_agent){
                $pid = $user['pid']?$user['pid']:SHOP_PID;
            }else{
                $boss = model('UserRelation')->alias('ur')->join('user u','u.uid=ur.boss_agent_uid')->where(['ur.uid'=>$this->uid,'u.boss_agent'=>1])->find();
                $pid = $boss['pid']?$boss['pid']:SHOP_PID;
            }
            session('pid',$pid);
            session('pidtime',time());
            session('partner_agent',$user->partner_agent);
            session('boss_agent',$user->boss_agent);
            $this->pid = $pid;
            $this->partner_agent = $user->partner_agent;
            $this->boss_agent = $user->boss_agent;
        }
        $this->weixinObj = new \wechat\WechatApi();
        $web_url = urldecode($request->param('web_url',''));
        $web_url = SERVER_PATH."/".$web_url;
        $this->wechatShare = $this->weixinObj->getJsSdkConfig($web_url);
        $this->wechatShare['shareImg'] = SERVER_PATH."/static/index/img/logo.png";
        $this->wechatShare['shareTitle'] = "奖购-领券神器+赚钱利器";
        $this->wechatShare['shareDesc'] = "用购物的钱来中奖";
        $web_url_arr = parse_url($web_url);
        if($web_url_arr['query']){
            $query_arr = explode("&", $web_url_arr['query']);
            $param_arr = array();
            foreach($query_arr as $v){
                $param = explode("=", $v);
                $param_arr[$param[0]] = $param[1]?:'';
            }
            $param_arr['inviter'] = $this->uid;
        }else{
            $param_arr['inviter'] = $this->uid;
        }
        $query_arr = array();
        foreach($param_arr as $k=>$v){
            $query_arr[] = "$k=$v";
        }
        $this->wechatShare['shareUrl'] = "https://".$web_url_arr['host'].$web_url_arr['path']."?".  implode("&", $query_arr);
//        $this->wechatShare['shareUrl'] = $web_url_arr['scheme']."://".$web_url_arr['host'].$web_url_arr['path']."?".  implode("&", $query_arr);
        parent::__construct();
    }
}

