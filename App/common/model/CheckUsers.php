<?php
namespace app\common\model;

class CheckUsers extends \think\Model
{
    protected $updateTime = false;
    protected $createTime = false;
    
    public static function getUserInfo($id){
        return User::where(['uid'=>$id])->find();
    }
    
    public static function getUserToken($userId,$api_token){
        return User::where(['uid'=>$userId,'api_token'=>$api_token])->find();
    }
}
