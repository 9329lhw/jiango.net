<?php

namespace app\ioslogic;

use think\Base;
use app\common\model\TbOrders;
use app\common\model\TbOrdersItem;
use app\common\model\UserPointLog;
use app\common\model\User;

class TbOrdersLogic extends Base {
    
    /**
     * 更新父订单信息
     * @author jiang
     */
    public function updateOrder($oid){
        $num = 0;
        $order = TbOrders::where('oid',$oid)->find();
        $order_items = TbOrdersItem::where('oid',$order->oid)->select()->toArray();
        $tb_status_arr = array_column($order_items, 'tb_status');
        $updateflag = false;
        
        if((in_array(3, $tb_status_arr) || in_array(14, $tb_status_arr))){
            if($order->status != 14){
                $order->status = 14;
                if($order->prize_status == 0){
                    $order->prize_date = date('Y-m-d');
                    $order->prize_status = 99;
                }
                if($order->jackpot_status == 0 && $order->jackpot_date = '0000-00-00'){
                    $order->jackpot_date = date('Y-m-d',strtotime('+1 day'));
                }
                $updateflag = true;
            }
        }elseif(in_array(12, $tb_status_arr)){
            if($order->status != 12){
                $order->status = 12;
                if($order->jackpot_status == 0){
                    $order->jackpot_date =  '0000-00-00';
                }
                if($order->prize_status == 99){
                    $order->prize_date = '0000-00-00';
                    $order->prize_status = 0;
                }
                $updateflag = true;
            }
        }elseif(in_array(13, $tb_status_arr)){
            if($order->status != 13){
                $order->status = 13;
                if($order->jackpot_status == 0){
                    $order->jackpot_date = '0000-00-00';
                }
                if($order->prize_status == 99){
                    $order->prize_date = '0000-00-00';
                    $order->prize_status = 0;
                }elseif($order->prize_status == 1){//中奖失效
                    $order->prize_status = 4;
                    $winners = db('winner')->alias('w')->field('w.*')->join('tb_orders o','o.oid=w.oid')->where(['w.oid'=>$oid,'o.prize_status'=>1,'w.status'=>1])->select();
                    if($winners){
                        foreach($winners as $w){
                            $pointLog = new UserPointLog;
                            $pointLog->uid = $w['uid'];
                            $pointLog->point = -$w['point'];
                            $pointLog->type = "failure_prize";
                            $pointLog->about_id = $w['oid'];
                            if($w['type'] == 'order'){
                                $pointLog->content = "订单失效直接获奖无效，订单号：".$w['trade_id'];
                            }elseif($w['type'] == 'second_agent'){
                                $pointLog->content = "下级用户订单失效获奖无效，订单号：".$w['trade_id'];
                            }elseif($w['type'] == 'first_agent'){
                                $pointLog->content = "下下级用户订单失效获奖无效，订单号：".$w['trade_id'];
                            }
                            $pointLog->save();
                            \app\admin\model\User::find($w['uid'])->save(['point' => ['exp', 'point-' . $w['point']], 'total_point' => ['exp', 'total_point-' . $w['point']]]);
                        }
                        db('winner')->where(['oid'=>$oid,'status'=>1])->update(['status'=>0]);
                    }
                }
                $updateflag = true;
            }
        }
        $total_pay_price = $total_settle_price = $total_forecast_income = $total_income = $total_commission_fee = $total_subsidy_fee = $jackpot_amount = 0;
        $earning_time = 0;
        foreach($order_items as $oi){
            $create_time = $oi['create_time'];
            $pid = $oi['pid'];
            if(in_array($oi['tb_status'],[3,12,14])){
                $total_pay_price += $oi['pay_price'];
                $jackpot_amount += $oi['income'];
                $total_settle_price += $oi['settle_price'];
                $total_forecast_income += $oi['forecast_income'];
                $total_income += $oi['income'];
                $total_commission_fee += $oi['commission_fee'];
                $total_subsidy_fee += $oi['subsidy_fee'];
            }
            $num += $oi['item_num'];
            $earning_time = $oi['earning_time']>$earning_time?$oi['earning_time']:$earning_time;
        }
        if($updateflag || $order->total_pay_price != $total_pay_price || $order->total_settle_price != $total_settle_price || $order->total_forecast_income != $total_forecast_income || $order->total_income != $total_income || $order->total_commission_fee != $total_commission_fee || $order->total_subsidy_fee != $total_subsidy_fee || $order->earning_time != $earning_time){
            $order->create_time = $create_time;
            $order->total_pay_price = $total_pay_price;
            $order->total_settle_price = $total_settle_price;
            $order->total_forecast_income = $total_forecast_income;
            $order->total_income = $total_income;
            $order->total_commission_fee = $total_commission_fee;
            $order->total_subsidy_fee = $total_subsidy_fee;
            $order->pid = $pid;
            $order->num = $num;
            $order->earning_time = $earning_time;
            if($order->jackpot_status == 0){
                $order->jackpot_amount = $jackpot_amount;
            }
            $order->save();
            if(session('admin_id')){
                model('AdminLog')->adminLogAdd("订单号{$order->trade_id}，状态{$this->tbStatusList[$order->status]}，奖池时间{$order->jackpot_date}，参奖时间{$order->prize_date}",'update','order');
            }
        }
    }
    
    /**
     * 领取订单
     * @author jiang
     */
    public function ledOrder($trade_id,$uid){
        if (empty($trade_id)) {
            return '订单号不能为空';
        }
        if (empty($uid)) {
            return '用户ID不能为空';
        }
        if(!preg_match('/^[0-9]+$/', $trade_id)){
            return '订单号格式错误，需为数字';
        }
        $order = TbOrders::get(['trade_id' => $trade_id]);
        if ($order) {
            if ($order->uid == 0) {
                $order->uid = $uid;
                $order->led_time = time();
                $result = $order->save();
                if ($result) {
                    return true;
                } else {
                    return '提交失败';
                }
            } else {
                if ($order->uid == $uid) {
                    return '您已经提交过该订单，请勿重复提交';
                } else {
                    return '该订单已被领取';
                }
            }
        } else {
            $order = new TbOrders;
            $order->trade_id = $trade_id;
            $order->uid = $uid;
            $order->led_time = time();
            $result = $order->save();
            if ($result) {
                return true;
            } else {
                return '提交失败';
            }
        }
    }
    
    /**
     * 删除订单
     * @author jiang
     */
    public function deleteOrder($trade_id,$uid){
        if (empty($trade_id)) {
            return '订单号不能为空';
        }
        if (empty($uid)) {
            return '用户ID不能为空';
        }
        $order = TbOrders::get(['trade_id' => $trade_id]);
        if ($order) {
            if ($order->uid != $uid) {
                return '无该订单权限';
            }
            if($order->status != 0 && $order->status != -1){
                return '无法删除';
            }
            $order->delete();
            return true;
        } else {
            return '订单不存在';
        }
    }
    
    /**
     * 好友订单列表
     * @author jiang
     */
    public function lists($uid,$where = array(),$page = 0,$pageSize = 10){
        $list = TbOrders::lists($uid,$where,$page,$pageSize);
        $data = array('list'=>[],'count'=>0,'total_page'=>0);
        foreach($list['list'] as $k=>$o){
            $data['list'][$k]['trade_id'] = $o['trade_id'];
            $data['list'][$k]['total_pay_price'] = $o['total_pay_price'];
            $data['list'][$k]['status'] = $o['status'];
            $data['list'][$k]['status_label'] = TbOrders::$tbStatusList[$o['status']];
            $data['list'][$k]['prize_status'] = $o['prize_status'];
            $data['list'][$k]['prize_status_label'] = TbOrders::$prizeStatusList[$o['prize_status']];
            $data['list'][$k]['create_time'] = date('Y-m-d H:i',$o['create_time'] == 0?$o['led_time']:$o['create_time']);
            $data['list'][$k]['item'] = array();
            if($o['status']){
                $data['list'][$k]['item'] = TbOrdersItem::where('oid',$o['oid'])->field('item_title,item_num,pay_price,pic_url')->select();
            }
        }
        $data['count'] = $list['count'];
        $data['total_page'] = $page?ceil($data['count']/$pageSize):1;
        return $data;
    }
    
    /**
     * 好友订单列表
     * @author jiang
     */
    public function agentOrders($uid,$where = array(),$page = 0,$pageSize = 10){
        $list = TbOrders::agentOrders($uid,$where,$page,$pageSize);
        $data = array('list'=>[],'count'=>0,'total_page'=>0);
        foreach($list['list'] as $k=>$o){
            $data['list'][$k]['trade_id'] = preg_replace('/^(\d{5})\d*(\d{2})$/', '$1******$2', $o['trade_id']);
            $data['list'][$k]['total_pay_price'] = $o['total_pay_price'];
            $data['list'][$k]['status'] = $o['status'];
            $data['list'][$k]['status_label'] = TbOrders::$tbStatusList[$o['status']];
            $data['list'][$k]['prize_status'] = $o['prize_status'];
            $data['list'][$k]['prize_status_label'] = TbOrders::$prizeStatusList[$o['prize_status']];
            $data['list'][$k]['create_time'] = date('Y-m-d H:i',$o['create_time']);
            $data['list'][$k]['item'] = array();
            if($o['status']){
                $data['list'][$k]['item'] = TbOrdersItem::where('oid',$o['oid'])->field('item_title,item_num,pay_price,pic_url')->select();
            }
        }
        $data['count'] = $list['count'];
        $data['total_page'] = $page?ceil($data['count']/$pageSize):1;
        return $data;
    }

    /**
     * 特殊代理订单列表
     * @author jiang
     */
    public function bossOrders($uid,$where = array(),$page = 0,$pageSize = 10){
        $list = TbOrders::bossOrders($uid,$where,$page,$pageSize);
        $data = array('list'=>[],'count'=>0,'total_page'=>0);
        foreach($list['list'] as $k=>$o){
            $data['list'][$k]['trade_id'] = preg_replace('/^(\d{5})\d*(\d{2})$/', '$1******$2', $o['trade_id']);
            $data['list'][$k]['total_pay_price'] = $o['total_pay_price'];
            $data['list'][$k]['status'] = $o['status'];
            $data['list'][$k]['commission'] = floor($o['total_forecast_income']*$o['boss_agent_rate']);
            $data['list'][$k]['complete'] = floatval($o['boss_agent_commission'])?1:0;
            $data['list'][$k]['status_label'] = TbOrders::$tbStatusList[$o['status']];
            $data['list'][$k]['create_time'] = date('Y-m-d',$o['create_time']);
            $data['list'][$k]['item'] = array();
            if($o['status']){
                $data['list'][$k]['item'] = TbOrdersItem::where('oid',$o['oid'])->field('item_title,item_num,pay_price,pic_url')->select();
            }
        }
        $data['count'] = $list['count'];
        $data['total_page'] = $page?ceil($data['count']/$pageSize):1;
        return $data;
    }
    
    /**
     * 订单导入
     * @author jiang
     */
    public function import($file = ''){
        if(!$file){
            return ['success'=>0,'insert'=>0,'update'=>0,'fail'=>[]];
        }
        import('PHPExcel.PHPExcel', EXTEND_PATH);
        $objPHPExcel = \PHPExcel_IOFactory::load($file);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumm = $sheet->getHighestColumn();
        $orderObj = model('TbOrders');
        $orderItemObj = model('TbOrdersItem');
        
        $success = $insert = $update = 0;
        $fail = array();
        $oid = $item_id = 0;//用于处理同一订单同一商品同一商品数
        $updateMain = false;//是否更新主订单
        for ($row = 2; $row <= $highestRow; $row++){
            $trade_id = $sheet->getCell('Y'.$row)->getValue();
            $num_iid = $sheet->getCell('D'.$row)->getValue();
            $item_num = $sheet->getCell('G'.$row)->getValue();
            $pay_price = $sheet->getCell('M'.$row)->getValue();
            $tb_status = array_search($sheet->getCell('I'.$row)->getValue(), TbOrders::$tbStatusList);
            if(!$tb_status){
                $fail[] = $trade_id.'状态错误'.PHP_EOL;
                continue;
            }
            $key = "{$num_iid}_{$item_num}";
            $settle_price = $sheet->getCell('O'.$row)->getValue();
            $forecast_income = $sheet->getCell('N'.$row)->getValue();
            $income_rate = $sheet->getCell('K'.$row)->getValue();
            $income_rate = gettype($income_rate) == 'double'?$income_rate*100:floatval($income_rate);
            $income = $sheet->getCell('P'.$row)->getValue();
            $commission_rate = $sheet->getCell('R'.$row)->getValue();
            $commission_rate = gettype($commission_rate) == 'double'?$commission_rate*100:floatval($commission_rate);
            $commission_fee = $sheet->getCell('S'.$row)->getValue();
            $subsidy_rate = $sheet->getCell('T'.$row)->getValue();
            $subsidy_rate = gettype($subsidy_rate) == 'double'?$subsidy_rate*100:floatval($subsidy_rate);
            $subsidy_fee = $sheet->getCell('U'.$row)->getValue();
            $divided_rate = $sheet->getCell('L'.$row)->getValue();
            $divided_rate = gettype($divided_rate) == 'double'?$divided_rate*100:floatval($divided_rate);
            $create_time = $sheet->getCell('A'.$row)->getValue();
            $earning_time = $sheet->getCell('Q'.$row)->getValue();
            $pid = 'mm_130792044_'.$sheet->getCell('AA'.$row)->getValue().'_'.$sheet->getCell('AC'.$row)->getValue();
            $order = $orderObj->where('trade_id',$trade_id)->find();
            if(!$order){
                $order = new TbOrders;
                $order->trade_id = $trade_id;
                $order->save();
                if(session('admin_id')){
                    model('AdminLog')->adminLogAdd("订单号{$order->trade_id}",'add','order');
                }
                $order = $orderObj::find($order->oid);
            }
            if($oid != $order->oid){
                if($oid && $updateMain){
                    $this->updateOrder($oid);
                }
                $oid = $order->oid;
                $item_id = 0;
                $updateMain = false;
            }
            
            //必须不按状态导入
            $where = ['trade_id'=>$trade_id,'num_iid'=>$num_iid,'item_num'=>$item_num];
            if($item_id){
                $where['id'] = ['gt',$item_id];
            }
            $order_item = $orderItemObj->where($where)->order('id')->find();
            
            if(!$order_item){
                $updateMain = true;
                $order_item = new TbOrdersItem;
                $order_item->oid = $order->oid;
                $order_item->trade_id = $trade_id;
                $order_item->num_iid = $num_iid;
                $order_item->item_title = $sheet->getCell('C'.$row)->getValue();
                $order_item->item_num = $item_num;
                $order_item->tb_status = $tb_status;
                $order_item->pay_price = $pay_price;
                $order_item->settle_price = $settle_price;
                $order_item->forecast_income = $forecast_income;
                $order_item->income_rate = $income_rate;
                $order_item->income = $income;
                $order_item->commission_rate = $commission_rate;
                $order_item->commission_fee = $commission_fee;
                $order_item->subsidy_rate = $subsidy_rate;
                $order_item->subsidy_fee = $subsidy_fee;
                $order_item->divided_rate = $divided_rate;
                $order_item->earning_time = strtotime($earning_time);
                $order_item->create_time = strtotime($create_time);
                $order_item->pid = $pid;
                //走crontab定时器
//                $pic_url = db('goods')->where('num_iid',$num_iid)->value('pic_url');
//                if(!$pic_url){
//                    $req = new \TbkItemInfoGetRequest;
//                    $req->setFields("num_iid,title,pict_url");
//                    $req->setNumIids($num_iid);
//                    ob_start();
//                    $resp = $c->execute($req);
//                    ob_clean();
//                    $resp = json_decode(json_encode($resp),true);
//                    $pic_url = $resp['results']['n_tbk_item']['pict_url'];
//                }
                $order_item->pic_url = '';
                $order_item->save();
                $insert++;
                if(session('admin_id')){
                    model('AdminLog')->adminLogAdd("ID{$order_item->id}，订单号{$order_item->trade_id}，商品名{$order_item->item_title}，状态{TbOrders::$tbStatusList[$tb_status]}",'add','orderItem');
                }
            }elseif($order_item->tb_status != $tb_status || $order_item->pay_price != $pay_price || $order_item->settle_price != $settle_price || $order_item->forecast_income != $forecast_income || $order_item->income != $income || $order_item->commission_fee != $commission_fee || $order_item->subsidy_fee != $subsidy_fee || $order_item->earning_time != strtotime($earning_time)){
                $order_item->tb_status = $tb_status;
                $order_item->pay_price = $pay_price;
                $order_item->settle_price = $settle_price;
                $order_item->forecast_income = $forecast_income;
                $order_item->income_rate = $income_rate;
                $order_item->income = $income;
                $order_item->commission_rate = $commission_rate;
                $order_item->commission_fee = $commission_fee;
                $order_item->subsidy_rate = $subsidy_rate;
                $order_item->subsidy_fee = $subsidy_fee;
                $order_item->divided_rate = $divided_rate;
                $order_item->earning_time = strtotime($earning_time);
                $order_item->save();
                $update++;
                $updateMain = true;
                if(session('admin_id')){
                    model('AdminLog')->adminLogAdd("ID{$order_item->id}，订单号{$order_item->trade_id}，商品名{$order_item->item_title}，状态{TbOrders::$tbStatusList[$tb_status]}",'update','orderItem');
                }
            }
            $item_id = $order_item->id;
            $success++;
        }
        if($oid){
            $this->updateOrder($oid);
        }
        return ['success'=>$success,'insert'=>$insert,'update'=>$update,'fail'=>$fail];
    }
    
    /**
     * 代言人订单归属结算
     * @author jiang
     */
    public function bossSettle(){
        TbOrders::alias('o')->join('user u','u.pid=o.pid')
                ->where(['u.boss_agent'=>['gt',0],'o.boss_agent_uid'=>0,'o.create_time'=>['exp','>u.boss_agent_time']])
                ->update(['o.boss_agent_uid'=>['exp','u.uid'],'boss_agent_rate'=>\lib\cache\CacheTool::configsCache('boss_agent_order_commission_rate')]);
        TbOrders::where(['boss_agent_uid'=>['gt',0],'boss_agent_commission'=>['exp',"<>floor(total_income*boss_agent_rate)"]])
                ->update(['boss_agent_commission'=>['exp',"floor(total_income*boss_agent_rate)"]]);
    }

}
