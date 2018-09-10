<?php

namespace app\ioslogic;
use think\Base;

class DtkGoodsLogic extends Base {
    protected $dtk_appkey = '70856bad5c';
    protected $dtk_page_size = 50; 
    protected $dtk_max_page = 800; 
    
    protected $hpt_appkey = 'byznb125j7';
    protected $hpt_page_size = 100;
    protected $hpt_max_page = 600;
    
    protected $tkjd_appkey = '5e3a6413dbf20d36dea956723315c502';
    protected $tkjd_page_size = 200;
    
    public function update($page) {
        $url = "http://api.dataoke.com/index.php?r=Port/index&type=total&appkey=".$this->dtk_appkey."&v=2&page=$page";
        echo $url;
        $goods_list = $this->doget($url);
        if (isset($goods_list['status'])) {
            exit('处理完毕');
        }
        $total_num = $goods_list['data']['total_num'];
        $total_page = ceil($total_num / $this->dtk_page_size);

        if ($page > $total_page) {
            exit('处理完毕');
        }

        if (empty($goods_list['result'])) {
            exit('处理完毕');
        }
        $actionobj = model('DtkGoods');
        foreach ($goods_list['result'] as $res) {
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
            $data['commission_rate'] = ($res['Org_Price']-$res['Price'])/$res['Org_Price']*100;
            $data['coupon_price'] = $res['Quan_price'];
            $data['coupon_rate'] = $res['Quan_price']/$res['Org_Price']*100;
            $data['coupon_end_time'] = $res['Quan_time'];
            $data['isshow'] = 1;
            $data['item_url'] = "https://uland.taobao.com/coupon/edetail?activityId=".$res['Quan_id']."&itemId=".$res['GoodsID'];
            $data['url_type'] = (!empty($res['IsTmall']) && $res['IsTmall'] == 1) ? 2 : 1;
            $data['seller_id'] = $res['SellerID'];
            $actionobj->saveData($data); 
        }
    }

    public function clear(){
       $actionobj = model('DtkGoods');
       $goods_list = $actionobj->getGoodsList();
       if(empty($goods_list)){
          exit('处理完毕'); 
       }
       $i = 0;
       foreach ($goods_list as $res){
          $data[$res['id']]['id'] = $res['id'];
          $data[$res['id']]['isshow'] = 0;
          $i ++ ;
       }
       $actionobj->saveAll($data);
       exit('共清除了'.$i.'条信息'); 
    }
    
    public function goods_queue(){
       $goods_queue = \lib\cache\RedisCache::instance()->lPop('goods_queue');
       $goods_queue_arr = json_decode($goods_queue,true);
       if(empty($goods_queue_arr)){
           exit('处理完毕'); 
       }
       $actionobj = model('TbGoods');
       foreach ($goods_queue_arr as $res){
           $id = $actionobj->getGoodsByNumIid($res['num_iid']);
           if($id){
              $data['id'] = $id; 
           }
           $data['num_iid'] = $res['num_iid'];
           $data['title'] = $res['title'];
           $data['pic_url'] = $res['pict_url'];
           $data['price'] = $res['zk_final_price'];
           $data['org_price'] = $res['reserve_price'];
           $data['item_url'] = "https://uland.taobao.com/coupon/edetail?activityId=&itemId=".$res['num_iid'];
           $data['isshow'] = 1;
           $data['seller_id'] = $res['seller_id'];
           $actionobj->saveData($data); 
       }
       
    }
    /**
     * 定时更新好品推
     * @param type $page
     */
    public function hpt_update($page) {
        $url = "http://api.open.youdanhui.com/index.php?action=index&type=total&appkey=".$this->hpt_appkey."&v=utf-8&page=$page";
        echo $url;
        $goods_list = $this->doget($url);
        
        $total_num = $goods_list['data']['total_num'];
        $total_page = ceil($total_num / $this->hpt_page_size);

        if ($page > $total_page) {
            exit('处理完毕');
        }

        if (empty($goods_list['data']['result'])) {
            exit('处理完毕');
        }
        $actionobj = model('HptGoods');
        foreach ($goods_list['data']['result'] as $res) {
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
            $data['commission_rate'] = ($res['Org_Price']-$res['Price'])/$res['Org_Price']*100;
            $data['coupon_price'] = $res['Quan_price'];
            $data['coupon_rate'] = $res['Quan_price']/$res['Org_Price']*100;
            $data['coupon_end_time'] = $res['Quan_time'];
            $data['isshow'] = 1;
            $data['item_url'] = "https://uland.taobao.com/coupon/edetail?activityId=".$res['Quan_id']."&itemId=".$res['GoodsID'];
            $data['url_type'] = (!empty($res['IsTmall']) && $res['IsTmall'] == 1) ? 2 : 1;
            $data['seller_id'] = $res['SellerID'];
            $actionobj->saveData($data); 
        }
    }
    
    /**
     * 定时更新淘客基地商品数据
     * @param type $page
     */
    public function tkjd_update($page) {
        $url = "http://api.tkjidi.com/getGoodsLink?appkey=".$this->tkjd_appkey."&type=www_lingquan&page=$page";
        echo $url;
        $goods_list = $this->doget($url);
        
        if($goods_list['status']!=200){
            exit('网络异常');
        }

        if (empty($goods_list['data'])) {
            exit('处理完毕');
        }
        
        $actionobj = model('TkjdGoods');
        foreach ($goods_list['data'] as $res) {
            $id = $actionobj->getGoodsByNumIid($res['goods_id']);
            if ($id) {
                $data['id'] = $id;
            }
            
            $data['cate_id'] = $res['cate_id'];
            $data['num_iid'] = $res['goods_id'];
            $data['title'] = $res['goods_name'];
            $data['intro'] = $res['quan_guid_content'];
            $data['pic_url'] = $res['pic'];
            $data['price'] = $res['price_after_coupons'];
            $data['org_price'] = $res['price'];
            $data['sale_num'] = $res['sales'];
            $data['coupon_id'] = $res['quan_id'];
            $data['commission_rate'] = ($res['price']-$res['price_after_coupons'])/$res['price']*100;
            $data['coupon_price'] = $res['price_coupons'];
            $data['coupon_rate'] = $res['price_coupons']/$res['price']*100;
            $data['coupon_end_time'] = $res['quan_expired_time'];
            $data['isshow'] = 1;
            $data['item_url'] = "https://uland.taobao.com/coupon/edetail?activityId=".$res['quan_id']."&itemId=".$res['goods_id'];
            $data['url_type'] = (!empty($res['src']) && $res['src'] == 1) ? 2 : 1;
            $data['seller_id'] = $res['seller_id'];
            $actionobj->saveData($data); 
        }
    }
    
    
    public function doget($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $notice = curl_exec($ch);
        return json_decode($notice,true);
    }
    
    
    
    public function tb_coupon_queue(){
       $coupon_queue_info = \lib\cache\RedisCache::instance()->lPop('tb_coupon_queue');
       $goods_queue_arr = json_decode($coupon_queue_info,true);
       $pid = $goods_queue_arr['pid'];
       if(empty($pid)){
           exit('处理完毕'); 
       }
       $goods_list = $goods_queue_arr['goods_list'];
       if(empty($goods_list)){
           exit('处理完毕'); 
       }
       $actionobj = model('TbCouponGoods');
       foreach ($goods_list as $res){
           $coupon_price = findNum($res['coupon_info']);
           $id = $actionobj->getGoodsByNumIid($res['num_iid'],$pid);
           if($id){
              $data['id'] = $id; 
           }
           $data['num_iid'] = $res['num_iid'];
           $data['pid'] = $pid;
           $data['title'] = $res['title'];
           $data['pic_url'] = $res['pict_url'];
           $data['price'] = $res['zk_final_price']-$coupon_price;
           $data['coupon_price'] = $coupon_price;
           $data['commission_rate'] = $res['commission_rate'];
           $data['coupon_end_time'] = $res['coupon_end_time'];
           $data['org_price'] = $res['zk_final_price'];
           $data['item_url'] = $res['coupon_click_url'];
           $data['isshow'] = 1;
           $data['seller_id'] = $res['seller_id'];
                
           $actionobj->saveData($data); 
       }
       
    }
}
