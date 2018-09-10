<?php

namespace app\ioslogic;

use think\Base;
use think\Result;
use app\common\model\User;
use app\common\model\AppLoginLogs;

class TokenLogic extends Base {
    
    /**
     * 
     * @param type $sessToken
     * @param type $userId
     * @param type $appVersion
     * @param type $device
     * @return type
     */
    public function checkSessToken($sessToken, $userId , $appVersion, $device) {

        if (empty($sessToken)) {
            Result::instance()->errCode(400)->fail('api_token不能为空')->output();
        }

        if (empty($appVersion)) {
            Result::instance()->errCode(400)->fail('version_name不能为空')->output();
        }
        if (empty($device)) {
            Result::instance()->errCode(400)->fail('设备号不能为空')->output();
        }

        if (empty($userId)) {
            Result::instance()->errCode(401)->fail('请登录')->output();
        }

        //从缓存文件中取版本信息
        /*$app_setting = get_device_version($device);
        file_put_contents(ROOT_PATH . '/extend/test.txt', var_export($app_setting, true));
        if (!empty($app_setting)) {
            //用户每天更新一次
            $time = date('Y-m-d', time());
            $login_info = AppLoginLogs::getUserLoginInfo($userId, $device, $time);

            if (empty($login_info)) {
                AppLoginLogs::create(['uid' => $userId, 'device' => $device, 'check_time' => $time]);
                // 版本号，只有 >= 1.1.0 的才去校验
                if (version_compare($appVersion, $app_setting['app_code']) == -1) {
                    Result::instance()->errCode(402)->fail('版本有更新，请下载最新版本', ['download_url' => $app_setting['download_url']])->output();
                }
            }
            file_put_contents(ROOT_PATH . '/extend/test1.txt', version_compare($appVersion, $app_setting['app_code']));
            //强制更新
            if (version_compare($appVersion, $app_setting['app_code']) == -1 && $app_setting['is_update']) {
                Result::instance()->errCode(403)->fail('请下载最新版本', ['download_url' => $app_setting['download_url']])->output();
            }
        }*/


        $tokenInfo = User::getUserToken($userId, $sessToken);
        if (empty($tokenInfo)) {
            Result::instance()->errCode(401)->fail('token已过期')->output();
        }

        return Result::instance()->msg('登录成功')->success();
    }

}
