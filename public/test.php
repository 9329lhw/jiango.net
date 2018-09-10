<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


// 回调地址
$url = urlencode("http://jianggo.net/test.php");
// 公众号的id和secret
$appid = 'wx4a7d29be47719738';
$appsecret = '2b46b4a949462a0013a2c3ab8cc59d37';
session_start();

// 获取code码，用于和微信服务器申请token。 注：依据OAuth2.0要求，此处授权登录需要用户端操作
if (!isset($_GET['code']) && !isset($_SESSION['code'])) {
    echo
    '<a href="https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid .
    '&redirect_uri=' . $url . '&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect">
  <font style="font-size:30">授权</font></a>';

    exit;
}

// 依据code码去获取openid和access_token，自己的后台服务器直接向微信服务器申请即可
if (isset($_GET['code']) && !isset($_SESSION['token'])) {
    $_SESSION['code'] = $_GET['code'];

    $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid .
            "&secret=" . $appsecret . "&code=" . $_GET['code'] . "&grant_type=authorization_code";
    $res = https_request($url);
    echo 3333;
    print_r($res);
    $res = (json_decode($res, true));
    $_SESSION['token'] = $res;
}


// 依据申请到的access_token和openid，申请Userinfo信息。
if (isset($_SESSION['token']['access_token'])) {
    $url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $_SESSION['token']['access_token'] . "&openid=" . $_SESSION['token']['openid'] . "&lang=zh_CN";
    echo $url;
    $res = https_request($url);
    echo 4444;
    print_r($res);
    $res = json_decode($res, true);

    $_SESSION['userinfo'] = $res;
}

// cURL函数简单封装
function https_request($url, $type = "get", $res = "json", $data = '') {
    $url = 'www.baidu.com';
//1.初始化curl
    $curl = curl_init();
//2.设置curl的参数
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl,CURLOPT_HEADER,0); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,10); 
    if ($type == "post") {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
//3.采集
    $output = curl_exec($curl);
    print_r($output);
//4.关闭
    curl_close($curl);
    if ($res == 'json') {
        return json_decode($output,true);
    }
}