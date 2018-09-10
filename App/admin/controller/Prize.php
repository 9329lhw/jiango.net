<?php

namespace app\admin\controller;

use app\admin\model\User;
use app\common\model\UserPointLog;

/**
 * 奖
 * @author jiang
 */
class Prize extends Base {
    
    /**
     * 往期开奖
     */
    public function history(){
        $list = db('prize')->where(['prize_day'=>['lt',date('Y-m-d',strtotime('-1 days'))]])->order('prize_day desc')->select();
        $this->assign('list',$list);
        $this->assign('ur_here', '往期开奖');
        return $this->display();
    }
    
    /**
     * 中奖名单
     */
    public function winners(){
        $day = $_REQUEST['day'];
        if(!$day){
            return $this->url_redirect("数据不存在", "history", "上一页");
        }
        $list = db('winner')->alias('w')->field('w.*,u.nickname')->where(['day'=>$day])->join('user u','u.uid=w.about_id','left')->select();
        $this->assign('list',$list);
        $this->assign('ur_here', $day.'获奖名单');
        $this->assign('action_link', array('text' => '往期开奖', 'href' => 'history'));
        return $this->display();
    }
    
    public function index(){
        $prize_rate = db('shop_config')->where(['code'=>'prize_rate'])->value('value');
        $prize_day = date('Y-m-d',strtotime('-1 days'));//参期时间
        $prize = db('prize')->where(['prize_day'=>$prize_day])->find();
        if(!$prize || !$prize['status']){
            //成交额
            $jackpot_amount = db('tb_orders')->where(['jackpot_date'=>$prize_day,'uid'=>['gt',0],'jackpot_status'=>0,'status'=>14])->sum('jackpot_amount');
            $data['order_winning_num'] = $data['second_agent_num'] = $data['first_agent_num'] = 0;
            if($prize && $prize['winning_data']){
                $winning_data = substr($prize['winning_data'],-1);
                //中奖人
                $prize_users = db('tb_orders')->where(['prize_date'=>$prize_day,'uid'=>['gt',0],'prize_status'=>99,"right(trade_id,1)"=>$winning_data])->group('trade_id')->column('uid');
                $data['order_winning_num'] = count($prize_users);
                $userRelationObj = model('UserRelation');
                foreach($prize_users as $u){
                    $relation = $userRelationObj->where(['uid'=>$u])->find();
                    if($relation['second_agent_uid']){
                        $data['second_agent_num']++;
                    }
                    if($relation['first_agent_uid']){
                        $data['first_agent_num']++;
                    }
                }
            }

            $data['actual_winning_num'] = $data['winning_num'] = $data['order_winning_num']+$data['second_agent_num']+$data['first_agent_num'];
            $data['income_amount'] = $jackpot_amount;
            $data['actual_jackpot'] = $data['jackpot'] = floor($jackpot_amount*$prize_rate);
            if($prize){
                $data['jackpot'] = $prize['jackpot']-$prize['actual_jackpot']+$data['actual_jackpot'];
                $data['winning_num'] += $prize['winning_num_increment'];
                $data['id'] = $prize['id'];
                db('prize')->update($data);
            }else{
                $data['prize_day'] = $prize_day;
                $data['prize_day'] = date('Y-m-d',strtotime('-1 days'));
                $data['publish_day'] = date('Y-m-d');
                db('prize')->insert($data);
            }
            $prize = db('prize')->where(['prize_day'=>$prize_day])->find();
        }
        
        $next_prize_day = date('Y-m-d');//参奖时间
        $next_prize = db('prize')->where([prize_day=>$next_prize_day])->find();
        //成交额
        $next_jackpot_amount = db('tb_orders')->where(['jackpot_date'=>$next_prize_day,'uid'=>['gt',0],'jackpot_status'=>0,'status'=>14])->sum('jackpot_amount');
        $data['income_amount'] = $next_jackpot_amount;
        $data['actual_jackpot'] = $data['jackpot'] = floor($next_jackpot_amount*$prize_rate);
        $data['id'] = 0;
        if($next_prize){
            $data['jackpot'] = $next_prize['jackpot']-$next_prize['actual_jackpot']+$data['actual_jackpot'];
            $data['id'] = $next_prize['id'];
            db('prize')->update($data);
        }else{
            $data[prize_day] = $next_prize_day;
            $data['prize_day'] = date('Y-m-d');
            $data['publish_day'] = date('Y-m-d',strtotime('+1 days'));
            db('prize')->insert($data);
        }
        $next_prize = db('prize')->where([prize_day=>$next_prize_day])->find();
        
        $this->assign('prize',$prize);
        $this->assign('next_prize',$next_prize);
        $this->assign('prize_rate',$prize_rate);
        $this->assign('ur_here', '开奖设置');
        return $this->display();
    }

    public function ajaxUpdatePrize(){
        $id = input('id',0);
        $act = input('act');
        $value = input('value');
        $prize = db('Prize')->where('id',$id)->find();
        if(!$prize){
            return json(array('error' => 4, 'message' => '数据不存在'));
        }
        if($prize['status']){
            return json(array('error' => 4, 'message' => '已开奖不得更改'));
        }
        $update = db('Prize')->where('id',$id)->update([$act=>$value]);
        if($update){
            model('AdminLog')->adminLogAdd("更新字段：$act-$value",'setup','prize');
            return json(array('error' => 0, 'message' => '修改成功'));
        }else{
            return json(array('error' => 4, 'message' => '修改失败'));
        }
    }
    
    public function updatePrize(){
        $id = input('id',0);
        $act = input('act');
        $value = input('value');
        $prize = db('Prize')->where('id',$id)->find();
        if(!$prize){
            return $this->url_redirect("数据不存在", "index", "上一页");
        }
        if($prize['status']){
            return $this->url_redirect("已开奖不得更改", "index", "上一页");
        }
        $update = db('Prize')->where('id',$id)->update([$act=>$value]);
        if($update){
            model('AdminLog')->adminLogAdd("更新字段：$act-$value",'setup','prize');
            return $this->url_redirect("", "index", "上一页",1);
        }else{
            return $this->url_redirect("", "index", "上一页",1);
        }
    }
    
    public function savePrizeRate(){
        db('shop_config')->where(['code'=>'prize_rate'])->update(['value'=>input('prize_rate/d','0')]);
        model('AdminLog')->adminLogAdd("设置奖池占比".input('prize_rate/d','0'),'','');
        return $this->url_redirect("", "index", "上一页", 1);
    }
    
    /**
     * 开奖
     */
    public function publish(){
        $prize_day = date('Y-m-d',strtotime('-1 days'));
        $prize = db('prize')->where([prize_day=>$prize_day])->find();
        if(!$prize){
            return $this->url_redirect("奖池不存在", "index", "开奖设置", 1);
        }elseif($prize['status']){
            return $this->url_redirect("已开奖，不得重复开奖", "index", "开奖设置", 1);
        }
        if(!$prize['winning_data']){
            return $this->url_redirect("缺少开奖标识，开奖失败", "index", "开奖设置", 1);
        }
        $prize_rate = db('shop_config')->where(['code'=>'prize_rate'])->value('value');
        //成交额
        $jackpot_amount = db('tb_orders')->where(['jackpot_date'=>$prize_day,'uid'=>['gt',0],'jackpot_status'=>0,'status'=>14])->sum('jackpot_amount');
        
        $data['order_winning_num'] = $data['second_agent_num'] = $data['first_agent_num'] = 0;
        $winning_data = substr($prize['winning_data'],-1);
        //中奖人
        $prize_orders = db('tb_orders')->where(['prize_date'=>$prize_day,'uid'=>['gt',0],'prize_status'=>99,"right(trade_id,1)"=>$winning_data])->select()->toArray();
        $data['order_winning_num'] = count($prize_orders);
        foreach($prize_orders as $k=>$o){
            $user = db('user')->alias('u')->field('u.uid,u.nickname,ur.first_agent_uid,ur.second_agent_uid')->where(['u.uid'=>$o['uid']])->join('user_relation ur','ur.uid=u.uid','left')->find();
            $prize_orders[$k]['user'] = $user;
            if($user['second_agent_uid']){
                $data['second_agent_num']++;
            }
            if($user['first_agent_uid']){
                $data['first_agent_num']++;
            }
        }
        $data['actual_winning_num'] = $data['winning_num'] = $data['order_winning_num']+$data['second_agent_num']+$data['first_agent_num'];
        $data['income_amount'] = $jackpot_amount;
        $data['actual_jackpot'] = $data['jackpot'] = floor($jackpot_amount*$prize_rate);
        $data['jackpot'] = $prize['jackpot']-$prize['actual_jackpot']+$data['actual_jackpot'];
        $data['winning_num'] += $prize['winning_num_increment'];
        $data['id'] = $prize['id'];
        $data['status'] = 1;
        db('prize')->update($data);
        $prize = db('prize')->where(['prize_day'=>$prize_day])->find();
        $point = floor($prize['jackpot']/$prize['winning_num']);
        //中奖
        $winner['day'] = $prize_day;
        $winner['point'] = $point;
        foreach($prize_orders as $o){
            $winner['uid'] = $o['uid'];
            $winner['user_name'] = $o['user']['nickname'];
            $winner['trade_id'] = $o['trade_id'];
            $winner['oid'] = $o['oid'];
            $winner['about_id'] = $o['uid'];
            $winner['type'] = 'order';
            db('winner')->insert($winner);
            $pointLog = new UserPointLog;
            $pointLog->uid = $o['uid'];
            $pointLog->point = $point;
            $pointLog->content = "订单获奖，订单号：".$o['trade_id'];
            $pointLog->type = "prize";
            $pointLog->about_id = $o['oid'];
            $pointLog->save();
            User::find($winner['uid'])->save(['point' => ['exp', 'point+' . $point], 'total_point' => ['exp', 'total_point+' . $point]]);
            if($o['user']['second_agent_uid']){
                $second_agent = User::find($o['user']['second_agent_uid']);
                $winner['uid'] = $second_agent['uid'];
                $winner['user_name'] = $second_agent['nickname'];
                $winner['type'] = 'second_agent';
                db('winner')->insert($winner);
                $pointLog = new UserPointLog;
                $pointLog->uid = $second_agent['uid'];
                $pointLog->point = $point;
                $pointLog->content = "好友订单获奖，订单号：".$o['trade_id'];
                $pointLog->type = "prize";
                $pointLog->about_id = $o['oid'];
                $pointLog->save();
                User::find($winner['uid'])->save(['point' => ['exp', 'point+' . $point], 'total_point' => ['exp', 'total_point+' . $point]]);
                if($o['user']['first_agent_uid']){
                    $first_agent = User::find($o['user']['first_agent_uid']);
                    $winner['uid'] = $first_agent['uid'];
                    $winner['user_name'] = $first_agent['nickname'];   
                    $winner['type'] = 'first_agent';
                    db('winner')->insert($winner);
                    $pointLog = new UserPointLog;
                    $pointLog->uid = $first_agent['uid'];
                    $pointLog->point = $point;
                    $pointLog->content = "好友的好友订单获奖，订单号：".$o['trade_id'];
                    $pointLog->type = "prize";
                    $pointLog->about_id = $o['oid'];
                    $pointLog->save();
                    User::find($winner['uid'])->save(['point' => ['exp', 'point+' . $point], 'total_point' => ['exp', 'total_point+' . $point]]);
                }
            }
        }
        //虚拟中奖
        if($prize['winning_num_increment']){
            $file = file(EXTEND_PATH.'name.txt',FILE_IGNORE_NEW_LINES);
            for($i=0;$i<$prize['winning_num_increment'];$i++){
                $trade_id = mt_rand(1,2);
                for($j=0;$j<16;$j++){
                    $trade_id .= mt_rand(0,9);
                }
                $trade_id .= $winning_data;
                $winner['uid'] = $winner['about_id'] = 0;
//                $winner['user_name'] = preg_replace('/(\S{1})([\S\s]*)(\S{1})/u','${1}***$3',$file[rand(0, 55936)]);
                $winner['user_name'] = $file[rand(0, 55936)];
                $winner['trade_id'] = $trade_id;
                $winner['type'] = 'virtual';
                $winner['oid'] = 0;
                db('winner')->insert($winner);
            }
        }
        //参奖订单处理
        db('tb_orders')->where(['prize_date'=>$prize_day,'uid'=>['gt',0],'prize_status'=>99,"right(trade_id,1)"=>$winning_data])->update(['prize_status'=>1]);
        db('tb_orders')->where(['prize_date'=>$prize_day,'uid'=>['gt',0],'prize_status'=>99])->update(['prize_status'=>2]);//未中奖
        db('tb_orders')->where(['prize_date'=>$prize_day,'uid'=>0,'prize_status'=>99])->update(['prize_status'=>3]);//未认领已参奖
        
        //奖池订单处理
        db('tb_orders')->where(['jackpot_date'=>$prize_day,'uid'=>['gt',0],'jackpot_status'=>0,'status'=>14])->update(['jackpot_status'=>1]);//已加入奖池
        db('tb_orders')->where(['jackpot_date'=>$prize_day,'uid'=>0,'jackpot_status'=>0,'status'=>['in',[3,14]]])->update(['jackpot_status'=>2]);//未认领处理
        model('AdminLog')->adminLogAdd("开奖",'','');
        return $this->url_redirect("开奖成功", "index", "", 1);
    }
    
}

