<?php
namespace app\logic;
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
    
    public function getCategoryListByType($board_id) {
        
        return [
            'category'=>get_category_by_type($board_id),
            'category_2'=>[
                'name'=>'聚划算',
                "url"=>"",
                "tag"=>"4",
                "extimg"=>"http://img.51yuexue.com/category/20180305/64fb94260bd0fbb6fd4641dae24814b4.jpg",
                "desc"=> ""
            ],
             'category_3'=>[
                'name'=>'9.9包邮',
                "url"=>"",
                "tag"=>"4",
                "extimg"=>"http://img.51yuexue.com/category/20180305/64fb94260bd0fbb6fd4641dae24814b4.jpg",
                "desc"=> ""
            ],
            'category_4'=>[
                "name"=>'淘抢购',
                "url"=>"",
                "tag"=>"4",
                "extimg"=>"http://img.51yuexue.com/category/20180305/64fb94260bd0fbb6fd4641dae24814b4.jpg",
                "desc"=> ""
            ],
        ];
    }

}

