<?php
namespace app\common\model;

class HptGoods extends \think\Model{
    
    public static function getList($page, $page_size, $where = '') {

        return DtkGoods::field('id,num_iid,title,price,org_price,coupon_price,pic_url,tbk_url,tg_url,sale_num,coupon_price,commission_rate,coupon_start_time,coupon_end_time,item_url,url_type')
                ->where('isshow = 1'.$where)
                ->order('commission desc')
                ->limit($page,$page_size)
                ->select();
    }
    
    public static function getGoodsById($id){
        return DtkGoods::field('id,num_iid,title,price,org_price,coupon_price,pic_url,tbk_url,tg_url,sale_num,coupon_start_time,coupon_end_time,item_url,url_type')->where(['id'=>$id])->find();
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
        $time = date('Y-m-d H:i:s');
        $where['coupon_end_time'] = array('elt',$time);
        $goodslist = $this->field('id')
                ->where($where)
                ->limit(500)
                ->select();
        return $goodslist;
       
    }
}

