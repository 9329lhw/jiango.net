<?php
namespace app\common\model;

class DtkGoods extends \think\Model{
    
    public static function getList($page, $page_size, $where = '',$order = '') {
        if(empty($order)){
            $order = 'update_time desc';
        }
        return DtkGoods::field('id,num_iid,title,intro,nick,sale_num,price,org_price,coupon_price,pic_url,coupon_price,commission,coupon_end_time,item_url,url_type,coupon_id')
                ->where('isshow = 1'.$where)
                ->order($order)
                ->limit($page,$page_size)
                ->select();
    }
    
    public static function getGoodsById($id){
        return DtkGoods::field('id,num_iid,title,intro,nick,sale_num,price,org_price,coupon_price,commission,pic_url,tbk_url,tg_url,sale_num,coupon_start_time,coupon_end_time,item_url,url_type,coupon_id')->where(['id'=>$id])->find();
    }
    
    public static function getGoodsByNumId($numid){
        return DtkGoods::field('id,num_iid,title,intro,nick,sale_num,price,org_price,coupon_price,commission,pic_url,coupon_end_time,item_url,url_type,coupon_id')->where(['num_iid'=>$numid])->find();
    }
    /*
     * @param type $num_iid
     * @return type
     */
    public function getGoodsByNumIid($num_iid){
        
        return $this->where(['num_iid'=>$num_iid])->value("id");
    }
    
    public function saveData($data){
        if($data[$this->getPk()] == ''){
            unset($data[$this->getPk()]);
            if($this->create($data)){
                return "添加成功";
            }else{
                return "添加成功";
            }
        }else{
            $this->isUpdate()->save($data);
            return "编辑成功";
        }
    }
    
    public function getGoodsList(){
        $where['coupon_end_time'] = array('elt',date('Y-m-d H:i:s'));
        $where['isshow'] = 1;
        $goodslist = $this->field('id')
                ->where($where)
                ->limit(500)
                ->order('id asc')
                ->select();
        return $goodslist;
       
    }
}

