<?php

namespace app\admin\controller;

use app\common\model\TbOrders;
use app\common\model\TbOrdersItem;
use app\common\model\UserPointLog;
use app\logic\TbOrdersLogic;

/**
 * 订单
 * @author jiang
 */
class Order extends Base {
    
    public function index() {
        $modelObj = model('TbOrders');
        $where = array();
        $input = input();
        if(!empty($input['nickname'])){
            $where['u.nickname'] = $input['nickname'];
        }
        if(!empty($input['trade_id'])){
            $where['o.trade_id'] = ['like',"%{$input['trade_id']}%"];
        }
        if(!empty($input['num_iid'])){
            $where['oi.num_iid'] = $input['num_iid'];
        }
        if(!empty($input['item_title'])){
            $where['oi.item_title'] = ['like',"%{$input['item_title']}%"];
        }
        if(is_numeric($input['tb_status'])){
            $where['oi.tb_status'] = $input['tb_status'];
        }
        if(is_numeric($input['prize_status'])){
            $where['o.prize_status'] = $input['prize_status'];
        }
        if(!empty($input['start_create_time']) && !empty($input['end_create_time'])){
            $where['o.create_time'] = ['between',[strtotime($input['start_create_time']),strtotime($input['end_create_time'].'+1 days')]];
        }else{
            if(!empty($input['start_create_time'])){
                $where['o.create_time'] = ['gt',strtotime($input['start_create_time'])];
            }
            if(!empty($input['end_create_time'])){
                $where['o.create_time'] = ['lt',strtotime($input['end_create_time'].'+1 days')];
            }
        }
        if(!empty($input['pid'])){
            $where['o.pid'] = $input['pid'];
        }
        if(!empty($input['boss_agent_uid'])){
            $where['o.boss_agent_uid'] = $input['boss_agent_uid'];
        }
        $sort_by = input('sort_by','o.create_time');
        $sort_order = input('sort_order','desc');
        $this->assign('sort_by',$sort_by);
        $this->assign('sort_order',$sort_order);
        $sort_flag = sortFlag($sort_by, $sort_order);
        $this->assign($sort_flag['tag'], $sort_flag['img']);
        $list = $modelObj->field('o.*,u.nickname,oi.*')->alias('o')->join('user u','u.uid=o.uid','left')->join('tb_orders_item oi','oi.oid=o.oid','left')->where($where)->group('o.oid')->order("$sort_by $sort_order")->paginate($this->pageSize);
        foreach($list as $k=>$v){
            if($v['num'] != $v['item_num']){
                $list[$k]['item'] = TbOrdersItem::where('oid',$v['oid'])->select();
                $list[$k]['item_num'] = count($list[$k]['item']);
            }else{
                $list[$k]['item_num'] = 1;
            }
        }
        $statistic = $modelObj->field('sum(total_pay_price) as total_pay_price,sum(total_income) as total_income')->alias('o')->join('user u','u.uid=o.uid','left')->join('tb_orders_item oi','oi.oid=o.oid','left')->where($where)->find()->toArray();
        $this->assign('list', $list);
        $this->assign('statistic', $statistic);
        $this->assign('tb_status_list', TbOrders::$tbStatusList);
        $this->assign('prize_status_list', TbOrders::$prizeStatusList);
        $this->assign('jackpot_status_list', TbOrders::$jackpotStatusList);
        $this->assign('page', $list->render());
        $pid = $modelObj->where('pid','neq','')->group('pid')->column('pid');
        foreach ($pid as $k=>$v){
            $pid[$v] = $v;
            unset($pid[$k]);
        }
        $this->assign('pid_list',$pid);
        $this->assign('boss_list',model('User')->where(['boss_agent'=>['gt',0]])->column('nickname','uid'));
        $this->assign('ur_here', '订单列表');
        return $this->display();
    }
    
    /**
     * 订单导入
     */
    public function import(){
        set_time_limit(0);
        ini_set('memory_limit', '400M');
        if(!$_FILES['import_file']['tmp_name']){
            return $this->url_redirect("请上传文件", "index", "上一页");
        }
        $result = TbOrdersLogic::instance()->import($_FILES['import_file']['tmp_name']);
        return $this->url_redirect("成功导入{$result['success']}条订单，其中新增订单{$result['insert']}条，更新订单{$result['update']}条".($result['fail']?'<br>失败订单'.count($result['fail']).'条：<br>'.PHP_EOL.  implode('<br>', $result['fail']):''), 'index', '订单列表',0);
    }
    
    /**
     * 上传文件
     */
    public function upload(){
        if(!$_FILES['file']['tmp_name']){
            return $this->url_redirect("请上传文件", "index", "上一页");
        }
        if(move_uploaded_file($_FILES['file']['tmp_name'],'/data/jianggo/TaokeDetail.xls')){
            return $this->url_redirect("上传成功", 'index', '订单列表',3);
        }else{
            return $this->url_redirect("上传失败", 'index', '订单列表',3);
        }
    }
    
}
