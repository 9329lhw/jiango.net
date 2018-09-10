<?php
namespace app\common\model;

class Category extends \think\Model
{
    /*
     * 获取分类列表
     * 
     * return array
     */
    public static function getCateList($board_id){
        
        return Category::field('id,name,url,tag,extimg,desc')->where(['status'=>1,'board_id'=>$board_id])->order('ordid desc')->select();
    }
}

