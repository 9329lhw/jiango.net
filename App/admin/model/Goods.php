<?php
namespace app\admin\model;

class Goods extends Base{
    
    /*
     * 获取广告列表
     * 
     * return array
     */
    public function getGoodsList($page_size){
        
        $goodslist = $this->where(['isshow'=>1])->order('commission desc,sale_num desc')->paginate($page_size);
        if(!empty($goodslist)){
            return $goodslist;
        }else{
            return [];
        }
    }
    /**
     * 
     * @param type $num_iid
     * @return type
     */
    public function getGoodsByNumIid($num_iid){
        
        $goodsinfo = $this->where(['num_iid'=>$num_iid])->value("id");
        return $goodsinfo;
    }
}

