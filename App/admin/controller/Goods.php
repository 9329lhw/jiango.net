<?php

namespace app\admin\controller;

class Goods extends Base
{
    public function _initialize() {
        parent::_initialize();
        $this->assign('lang', $GLOBALS['_LANG']);
    }
    
    public function index()
    {
        $actionobj = model('Goods'); 
        $goodslist = $actionobj->getGoodsList($this->pageSize);
        foreach ($goodslist as $key=>$res){
           $goodslist[$key]['coupon_start_time'] = date('Y-m-d H:i:s',$res['coupon_start_time']);
           $goodslist[$key]['coupon_end_time'] = date('Y-m-d H:i:s',$res['coupon_end_time']);
        }
        $this->assign('goodslist',$goodslist);
        $this->assign('page', $goodslist->render());
        $this->assign('ur_here', '商品管理');
        return $this->display();
    }
    
    public function detail()
    {
        $actionobj = model('Goods'); 
        $goodslist = $actionobj->getGoodsList();
        print_r($goodslist);
        $this->assign('goodslist',$goodslist);
        $this->assign('ur_here', '商品管理');
        return $this->display();
    }
}

