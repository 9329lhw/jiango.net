<?php
namespace app\ioslogic;
use think\Base;
use app\common\model\Category;

class CategoryLogic extends Base {
    
    public function getCategoryList($board_id) {
        $list = Category::getCateList($board_id);
        foreach ($list as $key => $res){
            $list[$key]['extimg'] = IMG_PATH.$res['extimg'];
        }
        return $list;
    }

}

