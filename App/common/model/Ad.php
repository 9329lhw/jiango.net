<?php
namespace app\common\model;

class Ad extends \think\Model
{
    protected $updateTime = false;
    protected $createTime = false;
    /**
     * 
     * @return type
     */
    public static function getAdList(){
        
        $list = Ad::field('id,type,param,url,tag,extimg,name,desc')->where(['status'=>1])->order('ordid desc')->limit(4)->select();
        return $list;
    }
    
    public static function getAdInfoById($id){
        return Ad::field('id,type,param,url,tag,extimg,name,desc,tbpwd')->where(['id'=>$id])->find();
    }
}
