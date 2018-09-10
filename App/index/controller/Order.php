<?php

namespace app\index\controller;

use app\logic\TbOrdersLogic;
use think\Result;
use app\common\model\TbOrders;
use app\common\model\TbOrdersItem;

/**
 * 订单接口
 * @author jiang
 */
class Order extends Base {
    
    public function lists() {
        $uid = $this->uid;
        $page = input('page/d',1);
        $page_size = input('page_size/d',10);
        $where['uid'] = $uid;
        $where['status'] = input('status','');
        $where['prize_status'] = input('prize_status','');
        $data =TbOrdersLogic::instance()->lists($where,$page,$page_size);
        if($page == 1){
            $data['statistic'] = TbOrders::statistic($uid,[0,14],[99,1]);
        }
        $data['wechat_share'] = $this->wechatShare;
        Result::instance()->data($data)->success('请求成功')->output();
    }

    /**
     * 认领订单
     * @author jiang
     */
    public function ledOrder() {
        $trade_id = input('trade_id', '');
        if (empty($trade_id)) {
            Result::instance()->fail('订单号不能为空')->output();
        }
        $uid = $this->uid;
        $result = TbOrdersLogic::instance()->ledOrder($trade_id, $uid);
        if($result === true){
            Result::instance()->success('提交成功')->output();
        }else{
            Result::instance()->fail($result)->output();
        }
    }
    
    /**
     * 删除订单
     * @author jiang
     */
    public function deleteOrder() {
        $trade_id = input('trade_id', '');
        if (empty($trade_id)) {
            Result::instance()->fail('订单号不能为空')->output();
        }
        $uid = $this->uid;
        $result = TbOrdersLogic::instance()->deleteOrder($trade_id, $uid);
        if($result === true){
            Result::instance()->success('删除成功')->output();
        }else{
            Result::instance()->fail($result)->output();
        }
    }
    
    /**
     * 好友订单
     * @author jiang
     */
    public function agentOrders(){
        $uid = $this->uid;
        $where['status'] = input('status/d','');
        $where['prize_status'] = input('prize_status/d','');
        $page = input('page/d',1);
        $page_size = input('page_size/d',10);
        $data = TbOrdersLogic::instance()->agentOrders($uid,$where,$page,$page_size);
        if($page == 1){
            $data['statistic'] = TbOrders::agentStatistic($uid,[14],[99,1]);
        }
        $data['wechat_share'] = $this->wechatShare;
        Result::instance()->data($data)->success('请求成功')->output();
    }

    /**
    * 代言人订单
    * @author jiang
    */
    public function bossOrders(){
        $uid = $this->uid;
        $userinfo = model('User')->get($uid);
        if($userinfo->boss_agent == 0){
            Result::instance()->fail('您不是代言人，没有此权限')->output();
        }
        $page = input('page/d',1);
        $page_size = input('page_size/d',10);
        $where['status'] = input('status/d','');
        $data = TbOrdersLogic::instance()->bossOrders($uid,$where,$page,$page_size);
        if($page == 1){
            $data['complete_earn'] = TbOrders::where(['boss_agent_uid'=>$uid])->sum('boss_agent_commission');//已完成收益
            $data['pending_earn'] = TbOrders::where(['boss_agent_uid'=>$uid,'total_income'=>0])->sum('floor(total_forecast_income*boss_agent_rate)');//待完成预估收益
            $data['statistic']['total'] = TbOrders::where(['boss_agent_uid'=>$uid])->count();
            $data['statistic']['complete'] = TbOrders::where(['boss_agent_uid'=>$uid,'total_income'=>['gt',0]])->count();
            $data['statistic']['pending'] = TbOrders::where(['boss_agent_uid'=>$uid,'total_income'=>0,'total_forecast_income'=>['gt',0]])->count();
        }
        $data['wechat_share'] = $this->wechatShare;
        Result::instance()->data($data)->success('请求成功')->output();
    }
    
    /**
    * 合伙人订单
    * @author jiang
    */
    public function partnerOrders(){
        $uid = $this->uid;
        $userinfo = model('User')->get($uid);
        if($userinfo->partner_agent == 0){
            Result::instance()->fail('您不是合伙人，没有此权限')->output();
        }
        $page = input('page/d',1);
        $page_size = input('page_size/d',10);
        $where['status'] = input('status/d','');
        $data = TbOrdersLogic::instance()->partnerOrders($uid,$where,$page,$page_size);
        if($page == 1){
            $data['complete_earn'] = TbOrders::where(['partner_agent_uid'=>$uid])->sum('partner_agent_commission');//已完成收益
            $data['pending_earn'] = TbOrders::where(['partner_agent_uid'=>$uid,'total_income'=>0])->sum('floor(total_forecast_income*partner_agent_rate)');//待完成预估收益
            $boss_complete_earn = TbOrders::where(['boss_agent_uid'=>$uid])->sum('boss_agent_commission');//已完成收益
            $data['complete_earn'] += $boss_complete_earn;
            $boss_pending_earn = TbOrders::where(['boss_agent_uid'=>$uid,'total_income'=>0])->sum('floor(total_forecast_income*boss_agent_rate)');//待完成预估收益
            $data['pending_earn'] += $boss_pending_earn;
            $data['statistic']['total'] = TbOrders::where(['partner_agent_uid|boss_agent_uid'=>$uid])->count();
            $data['statistic']['complete'] = TbOrders::where(['partner_agent_uid|boss_agent_uid'=>$uid,'total_income'=>['gt',0]])->count();
            $data['statistic']['pending'] = TbOrders::where(['partner_agent_uid|boss_agent_uid'=>$uid,'total_income'=>0,'total_forecast_income'=>['gt',0]])->count();
        }
        $data['wechat_share'] = $this->wechatShare;
        Result::instance()->data($data)->success('请求成功')->output();
    }
    
    /**
     * 订单返利
     */
    public function rebateIndex(){
        $data = TbOrdersLogic::instance()->rebateIndex($this->uid);
        $wechatShare = $this->wechatShare;
        $wechatShare['shareTitle'] = "领券购物即可参与100%抽奖";
        $wechatShare['shareDesc'] = "奖购-用购物的钱来中奖";
        $data['wechat_share'] = $wechatShare;
        Result::instance()->data($data)->success('请求成功')->output();
    }
        
    /**
     * 订单返利
     */
    public function rebate(){
        $uid = $this->uid;
        $data = TbOrdersLogic::instance()->rebate($uid);
        if(is_string($data)){
            Result::instance()->fail($data)->output();
        }else{
            Result::instance()->data($data)->success('请求成功')->output();
        }
    }

}
