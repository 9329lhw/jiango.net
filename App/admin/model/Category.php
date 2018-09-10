<?php
namespace app\admin\model;

class Category extends Base{
    
    /*
     * 获取广告列表
     * 
     * return array
     */
    public function getCateList(){
        
        $catelist = $this->where(['status'=>1])->select();
        if(!empty($catelist)){
            return $catelist;
        }else{
            return [];
        }
    }
}

