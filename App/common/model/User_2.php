<?php
namespace app\common\model;

class User extends \think\Model
{
    protected $updateTime = false;
    
    public static function getUserInfo($id){
        return User::where(['uid'=>$id])->find();
    }
    
    public static function getUserToken($userId,$api_token){
        return User::where(['uid'=>$userId,'api_token'=>$api_token])->find();
    }
}
