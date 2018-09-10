<?php
namespace app\index\controller;

class Wechat extends \think\Controller
{
    
    public function index()
    {
//        header('Content-Type:application/json; charset=utf-8');
        error_reporting(E_ERROR | E_PARSE);
        $base_url = SERVER_PATH;
//        $userModel = model('user');
//        var_dump($userModel->where(['uid'=>1])->find());
//        exit;
        
        $wechatObj = new \wechat\WechatApi();
        if($wechatObj->valid()){
            $wechatObj->responseMsg($base_url);
        }
    }
    
    public function setMenu(){
        if($_REQUEST['token'] != 'jianggo' || empty($_REQUEST['wechat']) || !config($_REQUEST['wechat'])){
            exit('error');
        }
        $wechat = $_REQUEST['wechat'];
        $menu = '{"button":[{"type":"click","name":"全网VIP电影","key":"video_vip"},{"type":"view","name":"领券中心","url":"'.SERVER_PATH.'/"},{"name":"订单红包","sub_button":[{"type":"click","name":"联系客服","key":"customer_service"},{"type":"view","name":"个人中心","url":"'.SERVER_PATH.'\/person"},{"type":"click","name":"推广海报","key":"promotion_qrcode"},{"type":"view","name":"新人红包","url":"'.'https://jianggo.rollguai.com/redPacketS"},{"type":"view","name":"订单红包","url":"'.SERVER_PATH.'/lotteryDraw"}]}]}';
//        $menu = '{"button":[{"type":"view","name":"订单红包","url":"'.SERVER_PATH.'/lotteryDraw"},{"type":"view","name":"领券中心","url":"'.SERVER_PATH.'/"},{"name":"加盟赚钱","sub_button":[{"type":"click","name":"联系客服","key":"customer_service"},{"type":"view","name":"新人红包","url":"'.'https://jianggo.rollguai.com/redPacketS"},{"type":"view","name":"个人中心","url":"'.SERVER_PATH.'\/person"},{"type":"click","name":"推广海报","key":"promotion_qrcode"},{"type":"click","name":"加盟赚钱","key":"strategy"}]}]}';
        $menu_arr = json_decode($menu, true);
        $wechatObj = new \wechat\WechatApi(config("$wechat.appid"),config("$wechat.appsecret"));
        var_dump($wechatObj->curl_menu($menu));
    }
    
    public function test(){
        if($_REQUEST['token'] != 'jianggo' || empty($_REQUEST['wechat']) || !config($_REQUEST['wechat'])){
            exit('error');
        }
        $wechat = $_REQUEST['wechat'];
        $wechatObj = new \wechat\WechatApi(config("$wechat.appid"),config("$wechat.appsecret"));
        var_dump($wechatObj->test());
    }
}

