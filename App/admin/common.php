<?php
/*
 * 添加操作记录
 * $sn         数据的唯一值
 * $action     操作的类型
 * $content    操作的内容
 */

function adminLogAdd($sn, $action, $content) {
    model('AdminLog')->adminLogAdd($sn, $action, $content);
}

/**
 * jiang
 * 检测权限
 */
function checkPriv($priv = '') {
    $actiion_list = session('action_list');
    if (empty($priv) || $actiion_list == 'all' || in_array(strtolower($priv), explode(',', strtolower($actiion_list)))) {
        return true;
    } else {
        return false;
    }
}

/**
 * 获得用户的真实IP地址
 */
function realIp() {
    static $realip = NULL;
    if ($realip !== NULL) {
        return $realip;
    }
    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
            foreach ($arr AS $ip) {
                $ip = trim($ip);
                if ($ip != 'unknown') {
                    $realip = $ip;
                    break;
                }
            }
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $realip = $_SERVER['REMOTE_ADDR'];
            } else {
                $realip = '0.0.0.0';
            }
        }
    } else {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $realip = getenv('HTTP_CLIENT_IP');
        } else {
            $realip = getenv('REMOTE_ADDR');
        }
    }
    preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
    $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
    return $realip;
}

/**
 * 上传图片
 * 
 * @param type $input
 * @param type $type
 * @param type $path
 * @return string
 */
function upload($input, $type = 1, $path = '') {
    if ($type == 1) {
        $path .= '/ad';
    } else if ($type == 2) {
        $path .= '/goods';
    } else if ($type == 3) {
        $path .= '/category';
    }
    if (!IS_LOCAL) {
        $upload_path = '/var/www/img.51yuexue.com' .$path;
    } else {
        $upload_path = ROOT_PATH . 'public' . DS . 'static\upload' . $path;
    }
    // 获取表单上传文件 例如上传了001.jpg
    $file = request()->file($input);
    // 移动到框架应用根目录/uploads/ 目录下
    $info = $file->move($upload_path);
    if ($info) {
        return $path.'/'.$info->getSaveName();
    } else {
        // 上传失败获取错误信息
        return '';
    }
}

function sortFlag($sort_by, $sort_order) {
    $flag['tag'] = 'sort_' . preg_replace('/^.*\./', '', $sort_by);
    $flag['img'] = '<img src="/static/admin/images/' . (strcasecmp($sort_order, "DESC") == 0 ? 'sort_desc.gif' : 'sort_asc.gif') . '"/>';
    return $flag;
}