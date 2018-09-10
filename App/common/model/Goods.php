<?php
namespace app\common\model;

class Goods extends \think\Model
{
    /*
     * 获取广告列表
     * 
     * return array
     */
    public static function getList($page, $page_size, $where = '') {

        return Goods::field('id,num_iid,title,price,pic_url,tbk_url,tg_url,sale_num,coupon_price,commission_rate,coupon_start_time,coupon_end_time,item_url')
                ->where('isshow = 1'.$where)
                ->order('commission desc')
                ->limit($page,$page_size)
                ->select();
    }
    /**
     * 
     * @param type $num_iid
     * @return type
     */
    public function getGoodsByNumIid($num_iid){
        
        return $this->where(['num_iid'=>$num_iid])->value("id");
    }
    
    public static function getGoodsById($id){
        return Goods::field('id,num_iid,title,price,pic_url,tbk_url,tg_url,sale_num,coupon_start_time,coupon_end_time,item_url')->where(['id'=>$id])->find();
    }
}

