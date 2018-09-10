<?php
namespace app\logic;
use think\Base;
use app\common\model\Keyword;

class KeywordLogic extends Base {
    
    public function getHotKeyword(){
        $keyword = Keyword::getKeywordList();
        $keyword = collection($keyword)->toArray();
        $key_num = array_rand($keyword,1);
        return empty($keyword[$key_num]['keyword']) ? '男装' : $keyword[$key_num]['keyword'];
    }
    
    public function upKeyword($keyword){
        $keyword_id = Keyword::getKeywordInfo($keyword);
        if($keyword_id){
            Keyword::where(['id'=>$keyword_id])->setInc('click_num');
        }else{
            Keyword::create(['keyword'=>$keyword]);
        }
    }

}

