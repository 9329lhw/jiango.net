<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace app\tebiedemingzi\controller;

use think\Controller;

class Crontab extends Controller{
    protected $request;
    protected $page_size = 50;
    
    public function test(){
        $page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
        
        $url = "http://api.dataoke.com/index.php?r=Port/index&type=total&appkey=70856bad5c&v=2&page=$page";
        $goods_list = $this->doget($url);
        
        if(isset($goods_list['status'])){
            exit('处理完毕'); 
        }
        $total_num = $goods_list['data']['total_num'];
        $total_page = ceil($total_num / $this->page_size);
        
        if($page > $total_page){
            exit('处理完毕'); 
        }
        
        if(empty($goods_list['result'])){
            exit('处理完毕'); 
        }
        $actionobj = model('DtkGoods'); 
        foreach ($goods_list['result'] as $res){
            $id = $actionobj->getGoodsByNumIid($res['GoodsID']);
            if ($id) {
                $data['id'] = $id;
            }
            $data['cate_id'] = $res['Cid'];
            $data['num_iid'] = $res['GoodsID'];
            $data['title'] = $res['D_title'];
            $data['intro'] = $res['Introduce'];
            $data['pic_url'] = $res['Pic'];
            $data['price'] = $res['Price'];
            $data['org_price'] = $res['Org_Price'];
            $data['sale_num'] = $res['Sales_num'];
            $data['tg_url'] = $res['Quan_link'];
            $data['coupon_id'] = $res['Quan_id'];
            $data['coupon_price'] = $res['Quan_price'];
            $data['coupon_rate'] = $res['Quan_receive'];
            $data['coupon_end_time'] = $res['Quan_time'];
            $data['seller_id'] = $res['SellerID'];
            $actionobj->saveData($data); 
        }
        exit("第 $page 处理完成");
    }
    
    public function doget($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $notice = curl_exec($ch);
        return json_decode($notice,true);
    }
}

