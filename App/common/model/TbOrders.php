<?php

namespace app\common\model;

class TbOrders extends \think\Model{
    
    protected $createTime = false;
    protected $updateTime = false;
    public static $tbStatusList = ['all'=>'全部','0'=>'追踪中','12'=>'订单付款','14'=>'订单成功','13'=>'订单失效','3'=>'订单结算','-1'=>'未经奖购购买'];
    public static $prizeStatusList = ['all'=>'全部','0'=>'未参奖','1'=>'中奖','2'=>'未中奖','3'=>'未认领参奖结束','4'=>'中奖失效','99'=>'参奖中'];
    public static $tbStatusNumList = ['0'=>'led_num','12'=>'paid_num','14'=>'success_num','13'=>'invalid_num','3'=>'settle_num'];
    public static $prizeStatusNumList = ['0'=>'unjoin_prize_num','1'=>'prize_num','2'=>'unprize_num','3'=>'unled_prize_num','4'=>'invalid_prize_num','99'=>'wait_prize_num'];
    public static $jackpotStatusList = ['all'=>'全部','0'=>'未加入','1'=>'已加入','2'=>'未认领处理'];

    /**
     * 列表
     * @author jiang
     */
    public static function lists($where,$page = 1,$pageSize = 15){
        if(empty($where['status']) && $where['status'] !== '0' && $where['status'] !== 0){
            unset($where['status']);
        }
        if(empty($where['prize_status']) && $where['prize_status'] !== '0'  && $where['prize_status'] !== 0){
            unset($where['prize_status']);
        }
        if($where['status'] === 0){
            unset($where['prize_status']);
        }
        if($where['prize_status'] == 2 || $where['prize_status'] == 3){
            $where['prize_status'] = ['in',[2,3]];
        }
        $count = self::where($where)->count();
        if($count){
            $list = self::where($where)->order('field(status,0) desc,create_time desc')->limit(($page-1)*$pageSize.",$pageSize")->select();
        }else{
            $list = [];
        }
       
        return array('list'=>$list,'count'=>$count);
    }
    
    /**
     * 获取统计数据
     * @author jiang
     */
    public static function statistic($uid = 0,$tbStatus = array(),$prizeStatus = array()){
        $statistic = [];
        $where = ['uid'=>$uid];
        $statistic['total'] = self::where($where)->count();
        if($tbStatus){
            foreach($tbStatus as $v){
                $where['status'] = $v;
                $statistic[self::$tbStatusNumList[$v]] = self::where($where)->count();
            }
        }
        if($prizeStatus){
            foreach($prizeStatus as $v){
                $where = ['uid'=>$uid];
                $where['prize_status'] = $v;
                $statistic[self::$prizeStatusNumList[$v]] = self::where($where)->count();
            }
        }
        if(!$tbStatus && !$prizeStatus){
            foreach(array_slice(self::$tbStatusList,1,4,true) as $k=>$v){
                $where['status'] = $k;
                $statistic[self::$tbStatusNumList[$k]] = self::where($where)->count();
            }
            foreach(array_slice(self::$prizeStatusList,1,null,true) as $k=>$v){
                $where = ['uid'=>$uid];
                $where['prize_status'] = $k;
                $statistic[self::$prizeStatusNumList[$k]] = self::where($where)->count();
            }
        }
        
        return $statistic;
    }
    
    /**
     * 好友订单列表
     * @author jiang
     */
    public static function agentOrders($uid,$where = array(),$page = 0,$pageSize = 10){
        if(empty($where['status']) && $where['status'] !== '0'  && $where['status'] !== 0){
            $where['status'] = ['gt',0];
//            unset($where['status']);
        }
        if(empty($where['prize_status']) && $where['prize_status'] !== '0'  && $where['prize_status'] !== 0){
            unset($where['prize_status']);
        }
        if($where['status'] === 0){
            unset($where['prize_status']);
        }
        $where['ur.first_agent_uid|ur.second_agent_uid'] = $uid;
        $count = self::alias('o')->where($where)->join('user_relation ur','ur.uid=o.uid')->count();
        $list = [];
        if($count){
            $list = self::alias('o')->where($where)->field('o.*')->join('user_relation ur','ur.uid=o.uid')->order('create_time desc')->limit(($page-1)*$pageSize.",$pageSize")->select();
        }
        return ['count'=>$count,'list'=>$list];
    }
    
    /**
     * 获取好友订单统计数据
     * @author jiang
     */
    public static function agentStatistic($uid = 0,$tbStatus = array(),$prizeStatus = array()){
        $statistic = [];
        $where['ur.first_agent_uid|ur.second_agent_uid'] = $uid;
        $statistic['total'] = self::alias('o')->where($where)->join('user_relation ur','ur.uid=o.uid')->count();
        if($tbStatus){
            foreach($tbStatus as $v){
                $where['status'] = $v;
                $statistic[self::$tbStatusNumList[$v]] = self::alias('o')->where($where)->join('user_relation ur','ur.uid=o.uid')->count();
            }
        }
        if($prizeStatus){
            foreach($prizeStatus as $v){
                $where = ['ur.first_agent_uid|ur.second_agent_uid'=>$uid];
                $where['prize_status'] = $v;
                $statistic[self::$prizeStatusNumList[$v]] = self::alias('o')->where($where)->join('user_relation ur','ur.uid=o.uid')->count();
            }
        }
        if(!$tbStatus && !$prizeStatus){
            foreach(array_slice(self::$tbStatusList,1,4,true) as $k=>$v){
                $where['status'] = $k;
                $statistic[self::$tbStatusNumList[$k]] = self::alias('o')->where($where)->join('user_relation ur','ur.uid=o.uid')->count();
            }
            foreach(array_slice(self::$prizeStatusList,1,null,true) as $k=>$v){
                $where = ['uid'=>$uid];
                $where['prize_status'] = $k;
                $statistic[self::$prizeStatusNumList[$k]] = self::alias('o')->where($where)->join('user_relation ur','ur.uid=o.uid')->count();
            }
        }
        
        return $statistic;
    }
    
    /**
     * 代言人订单列表
     * @author jiang
     */
    public static function bossOrders($uid,$where = array(),$page = 0,$pageSize = 10){
        if(empty($where['status']) && $where['status'] !== '0'  && $where['status'] !== 0){
            $where['status'] = ['gt',0];
        }
        $where['boss_agent_uid'] = $uid;
        $count = self::where($where)->count();
        $list = [];
        if($count){
            $list = self::where($where)->order('create_time desc')->limit(($page-1)*$pageSize.",$pageSize")->select();
        }
        return ['count'=>$count,'list'=>$list];
    }
    
    /**
     * 合伙人订单列表
     * @author jiang
     */
    public static function partnerOrders($uid,$where = array(),$page = 0,$pageSize = 10){
        if(!$where['status']){
            $where['status'] = ['gt',0];
        }
        $where['partner_agent_uid|boss_agent_uid'] = $uid;
        $count = self::where($where)->count();
        $list = [];
        if($count){
            $list = self::where($where)->order('create_time desc')->limit(($page-1)*$pageSize.",$pageSize")->select();
        }
        return ['count'=>$count,'list'=>$list];
    }
    
}