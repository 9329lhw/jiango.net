<?php
namespace app\common\model;

class AppLoginLogs extends \think\Model
{
    protected $updateTime = false;
    
    public static function getUserLoginInfo($id, $device, $time) {
        return AppLoginLogs::where(['uid' => $id, 'device' => $device, 'check_time' => $time])->find();
    }

}
