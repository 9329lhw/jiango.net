<?php
namespace app\admin\model;

class Ad extends Base{
    
    /**
     * 
     * @param type $page_size
     * @return type
     */
    public function getAdList($page_size){
        
        $adlist = $this->where(['status'=>1])->paginate($page_size);
        if(!empty($adlist)){
            return $adlist;
        }else{
            return [];
        }
    }
}

