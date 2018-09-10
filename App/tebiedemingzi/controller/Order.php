<?php

namespace app\tebiedemingzi\controller;

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
//        $input['start_create_time'] = $input['start_create_time']?:date('Y-m-d',strtotime('-7 days'));
//        $input['end_create_time'] = $input['end_create_time']?:date('Y-m-d');
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
        $sort_by = input('sort_by','create_time');
        $sort_order = input('sort_order','desc');
        if($sort_by=='create_time' && $sort_order=="ASC"){
            $where['o.status'] = ['not in',[0,-1]];
        }
        $this->assign('sort_by',$sort_by);
        $this->assign('sort_order',$sort_order);
        $sort_flag = sortFlag($sort_by, $sort_order);
        $this->assign($sort_flag['tag'], $sort_flag['img']);
        $this->assign('tb_status_list', TbOrders::$tbStatusList);
        $this->assign('prize_status_list', TbOrders::$prizeStatusList);
        $this->assign('jackpot_status_list', TbOrders::$jackpotStatusList);
        $modelObj->alias('o');
//        if(!empty($input['nickname'])){
//            $modelObj->join('user u','u.uid=o.uid');
//        }
//        if(!empty($input['num_iid']) || !empty($input['item_title']) || is_numeric($input['tb_status'])){
//            $modelObj->join('tb_orders_item oi','oi.oid=o.oid');
//        }
        $count = $modelObj->where($where)->join('user u','u.uid=o.uid','left')->join('tb_orders_item oi','oi.oid=o.oid','left')->count('distinct o.oid');
        $list = $modelObj->field('oi.*,u.nickname,o.*')->alias('o')->join('user u','u.uid=o.uid','left')->join('tb_orders_item oi','oi.oid=o.oid','left')->where($where)->group('o.oid')->order("o.$sort_by $sort_order")->paginate($this->pageSize,$count);
        foreach($list as $k=>$v){
            if($v['num'] != $v['item_num']){
                $list[$k]['item'] = TbOrdersItem::where('oid',$v['oid'])->select();
                $list[$k]['item_num'] = count($list[$k]['item']);
            }else{
                $list[$k]['item_num'] = 1;
            }
        }
        $statistic = $modelObj->field('sum(total_pay_price) as total_pay_price,sum(total_income) as total_income')->alias('o')->join('tb_orders_item oi','oi.oid=o.oid','left')->join('user u','u.uid=o.uid','left')->where($where)->find()->toArray();
        $this->assign('list', $list);
        $this->assign('statistic', $statistic);
        $this->assign('page', $list->render());
        $pid = $modelObj->where('pid','neq','')->group('pid')->column('pid');
        foreach ($pid as $k=>$v){
            $pid[$v] = $v;
            unset($pid[$k]);
        }
        $this->assign('pid_list',$pid);
        $this->assign('boss_list',model('User')->where(['boss_agent'=>['gt',0]])->column('nickname','uid'));
        $this->assign('ur_here', '订单列表');
        $this->assign('request', $input);
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
     * 订单导出
     */
    public function export(){
        import('PHPExcel.PHPExcel', EXTEND_PATH);
        $objPHPExcel = new \PHPExcel();

        $objPHPExcel->getProperties()->setCreator("JIANGGO")
                ->setLastModifiedBy("1X")
                ->setTitle('data')
                ->setSubject("楚门科技有限公司")
                ->setKeywords("office 2007 openxml php")
                ->setCategory("Export From JIANGGO");
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setCellValue('A1', '订单号');
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objPHPExcel->getActiveSheet()->setCellValue('B1', '所属用户');
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $objPHPExcel->getActiveSheet()->setCellValue('C1', '商品ID');
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $objPHPExcel->getActiveSheet()->setCellValue('D1', '商品名');
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(70);
        $objPHPExcel->getActiveSheet()->setCellValue('E1', '商品数');
        $objPHPExcel->getActiveSheet()->setCellValue('F1', '实付金额');
        $objPHPExcel->getActiveSheet()->setCellValue('G1', '结算金额');
        $objPHPExcel->getActiveSheet()->setCellValue('H1', '收入比率');
        $objPHPExcel->getActiveSheet()->setCellValue('I1', '收入金额');
        $objPHPExcel->getActiveSheet()->setCellValue('J1', '下单时间');
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $objPHPExcel->getActiveSheet()->setCellValue('K1', '订单状态');
        $objPHPExcel->getActiveSheet()->setCellValue('L1', 'pid');
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(25);
        
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
        $sort_by = input('sort_by','create_time');
        $sort_order = input('sort_order','desc');
        $list = $modelObj->field('oi.*,u.nickname,o.*')->alias('o')->join('user u','u.uid=o.uid','left')->join('tb_orders_item oi','oi.oid=o.oid','left')->where($where)->order("o.$sort_by $sort_order")->select();
        $k = 2;
        foreach($list as $v){
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $k, ' '.$v['trade_id'], \PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $k, $v['nickname']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $k, ' '.$v['num_iid']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $k, $v['item_title']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $k, $v['num']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $k, $v['total_pay_price']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $k, $v['total_settle_price']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $k, $v['income_rate'].'%');
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $k, $v['total_income']);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $k, date('Y-m-d H:i:s',$v['create_time']));
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $k, TbOrders::$tbStatusList[$v['tb_status']]);
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $k, $v['pid']);
            $k++;
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=订单列表.xls");
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
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
    
    public function refundlist() {
        $modelObj = model('TbOrdersRefund');
        $where = array();
        $input = input();
        if(!empty($input['nickname'])){
            $where['u.nickname'] = $input['nickname'];
        }
        if(!empty($input['trade_id'])){
            $where['r.trade_id'] = ['like',"%{$input['trade_id']}%"];
        }
        if(!empty($input['num_iid'])){
            $where['r.num_iid'] = $input['num_iid'];
        }
        if(!empty($input['item_title'])){
            $where['r.item_title'] = ['like',"%{$input['item_title']}%"];
        }
        if(!empty($input['start_create_time']) && !empty($input['end_create_time'])){
            $where['r.create_time'] = ['between',[strtotime($input['start_create_time']),strtotime($input['end_create_time'].'+1 days')]];
        }else{
            if(!empty($input['start_create_time'])){
                $where['r.create_time'] = ['gt',strtotime($input['start_create_time'])];
            }
            if(!empty($input['end_create_time'])){
                $where['r.create_time'] = ['lt',strtotime($input['end_create_time'].'+1 days')];
            }
        }
        $sort_by = input('sort_by','create_time');
        $sort_order = input('sort_order','desc');
        $this->assign('sort_by',$sort_by);
        $this->assign('sort_order',$sort_order);
        $sort_flag = sortFlag($sort_by, $sort_order);
        $this->assign($sort_flag['tag'], $sort_flag['img']);
        $modelObj->alias('r');
        $count = $modelObj->where($where)->join('tb_orders o','o.oid=r.oid')->join('user u','u.uid=o.uid','left')->count('distinct r.oid');
        $list = $modelObj->alias('r')->field('r.*,u.nickname')->join('tb_orders o','o.oid=r.oid')->join('user u','u.uid=o.uid','left')->where($where)->order("r.$sort_by $sort_order")->paginate($this->pageSize,$count);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        $this->assign('ur_here', '维权列表');
        $this->assign('request', $input);
        return $this->display();
    }
    
    /**
     * 维权订单导入
     */
    public function refundImport(){
        set_time_limit(0);
        ini_set('memory_limit', '400M');
        if(!$_FILES['import_file']['tmp_name']){
            return $this->url_redirect("请上传文件", "", "上一页");
        }
        $result = TbOrdersLogic::instance()->refundImport($_FILES['import_file']['tmp_name']);
        return $this->url_redirect("成功导入{$result['success']}条订单", 'refundlist', '订单列表',0);
    }
}
