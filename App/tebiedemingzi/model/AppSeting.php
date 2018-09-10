<?php
namespace app\tebiedemingzi\model;

class AppSeting extends Base{
    
    /**
     * 
     * @param type $page_size
     * @return type
     */
    public function getAppList($page_size){
        
        $applist = $this->select();
         return $applist;
    }
}

