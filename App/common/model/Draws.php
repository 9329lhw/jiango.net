<?php
namespace app\common\model;

class Draws extends \think\Model
{
    public static function getDrawsList($uid,$page, $page_size) {
        return Draws::where(['uid' => $uid])->order('create_time desc')->limit($page,$page_size)
                ->select();
    }
    
    public static function getDrawsListByUser($uid) {
        return Draws::where(['uid' => $uid, 'status' => ['in',[0,1]]])->find();
    }
    
}

