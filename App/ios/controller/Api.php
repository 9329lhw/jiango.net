<?php

namespace app\ios\controller;
use think\Controller;
use app\ioslogic\TokenLogic;

class Api extends Controller {
    
    protected $pid;
    protected $device;
    public $pageSize = 20; //分页数

    protected function _initialize() {
        
        $this->init();    //检查资源类型
    }
    /**
     * 初始化方法
     * 检测请求类型，数据格式等操作
     */
    public function init()
    {
        $this->device = $this->request->post('device');
        // 校验 会话Token 信息
        $api_token = $this->request->post('api_token');
        $userId = $this->request->post('user_id');
        $appVersion = $this->request->post('version_name');
        $result = TokenLogic::instance()->checkSessToken($api_token, $userId, $appVersion, $this->device);
        if ($result->isFail()) {
            $result->output();
        }
    }
}

/*
 * $app_setting = get_device_version($device);
        
        $time = date('Y-m-d',time());
        $login_info = AppLoginLogs::getUserLoginInfo($userId,$app_setting
['app_code'],$time);
        if (empty($login_info)) {
            AppLoginLogs::create(['uid'=>$userId,'device'=>$device,'check_time'=>
$time]);
            // 版本号，只有 >= 1.1.0 的才去校验
            if (version_compare($appVersion, $app_setting['app_code']) == 1) {
                Result::instance()->errCode(402)->fail('版本有更新，请下载最新版
本')->output();
            }
        }
        echo $appVersion;
        echo $app_setting['app_code'];
        echo version_compare($appVersion, $app_setting['app_code']);
        if(version_compare($appVersion, $app_setting['app_code']) == 1 && 
$app_setting['status']){
            Result::instance()->errCode(403)->fail('请下载最新版本')->output();
        }
 */
