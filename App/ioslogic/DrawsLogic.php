<?php
namespace app\ioslogic;
use think\Base;
use think\Result;
use app\common\model\Draws;
use app\common\model\User;

class DrawsLogic extends Base {
    
    protected $point_rate = 100;
    
    public function aplayDraws($uid,$amount) {
        if(empty($uid)){
            Result::instance()->fail('用户id不能为空')->output();
        }
        if(empty($amount)){
            Result::instance()->fail('申请的金额不能不能为空')->output();
        }
        if($amount < 10){
            Result::instance()->fail('申请的金额不能小于10元')->output();
        }
        //判断用户存不存在
        $userinfo = User::getUserInfo($uid);
        if(empty($userinfo)){
            Result::instance()->fail('用户不存在')->output();
        }
        //判断积分够不够
        if ($userinfo['enabled_point'] < $amount * $this->point_rate) {
            Result::instance()->fail('你的积分还不够')->output();
        }
        //
        if (empty($userinfo['aplay_number'])) {
            Result::instance()->fail('你还没有完善提现信息')->output();
        }
        //判断用户是否有未审核的记录
        $drawslogs = Draws::getDrawsListByUser($uid);
        if (!empty($drawslogs)) {
            Result::instance()->fail('您还有未完成的提现')->output();
        }
        
        //新增提现记录
        $userdraws = [
            'uid'=>$uid,
            'amount'=>$amount,
            'aplay_number'=>$userinfo['aplay_number'],
            'tel'=>$userinfo['tel'],
            'realname'=>$userinfo['realname'],
            'desc'=>'申请提现',
        ];
        model("Draws")->save($userdraws);
    }
    
    public function getDrawsList($uid,$page,$page_size) {
        if(empty($uid)){
            Result::instance()->fail('用户id不能为空')->output();
        }
        $pre = ($page-1)*$page_size;
        $logs = Draws::getDrawsList($uid,$pre,$page_size);
        if(empty($logs)){
            Result::instance()->fail('我是有底线的')->output();
        }
        return $logs;
    }
    
    public function checkDraws($uid,$tel,$real_name,$aplay_number) {
        if(empty($uid)){
            Result::instance()->fail('用户id不能为空')->output();
        }
        if(empty($aplay_number)){
            Result::instance()->fail('支付宝账号不能为空')->output();
        }
        if(empty($real_name)){
            Result::instance()->fail('真实姓名不能为空')->output();
        }
        if(empty($uid)){
            Result::instance()->fail('用户id不能为空')->output();
        }
        if(empty($tel)){
            Result::instance()->fail('联系手机号不能为空')->output();
        }
        
        $res = User::where(['uid'=>$uid])->update(['aplay_number'=>$aplay_number, 'realname'=>$real_name, 'tel' =>$tel]);
        if($res === FALSE){
           Result::instance()->fail('添加失败')->output(); 
        }
        
    }
    
     public function getAplayInfo($uid) {
        if(empty($uid)){
            Result::instance()->fail('用户id不能为空')->output();
        }
        $userinfo = User::getUserInfo($uid);
        if(empty($userinfo)){
           Result::instance()->fail('该用户不存在')->output(); 
        }
        return [
            'uid' => $uid,
            'enabled_point' => $userinfo['enabled_point'],
            'undraws_point' => $userinfo['total_point'] - $userinfo['enabled_point'],
            'draws_money' => $userinfo['enabled_point']/$this->point_rate,
            'realname' => $userinfo['realname'],
            'aplay_number' => $userinfo['aplay_number'],
            'tel' => $userinfo['tel']
        ];
        
    }

}

