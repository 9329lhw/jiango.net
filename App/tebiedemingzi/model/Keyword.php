<?php
namespace app\tebiedemingzi\model;

class Keyword extends Base{
    
    /**
     * 
     * @param type $page_size
     * @return type
     */
    public function getKeywordList($page_size){
        return $this->where(['status'=>1])->order('recommend desc,ordid asc,click_num desc')->paginate($page_size);
    }
}

