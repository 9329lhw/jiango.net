<?php
namespace app\common\model;

class Keyword extends \think\Model
{
    /**
     * 
     * @return type
     */
    public static function getKeywordList(){
        
        return Keyword::field('keyword')->where(['status'=>1])->order('recommend desc,ordid asc,click_num desc')->limit(5)->select();
    }
    
    public static function getKeywordInfo($keyword){
        
        return  Keyword::where("keyword like '%$keyword%'")->value("id");
    }
    
}
