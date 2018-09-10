<?php
namespace app\tebiedemingzi\controller;

use think\Controller;

class Base extends Controller{
    public $pageSize = 15; //分页数
    public $_LANG;
    public $_CFG;
    
    public function __construct(\think\Request $request = null) {
        error_reporting(E_ERROR | E_PARSE);
        
        require(EXTEND_PATH.'lib/languages/admin/common.php');
        parent::__construct($request);
        $no_check_arr = array('login', 'checklogin', 'check_verify','main'); //不进行登录检测的方法
        if (!in_array($request->action(), $no_check_arr) && session('admin_id') == '') {
            $this->redirect('/tebiedemingzi/index/login');
            exit;
        }
//        require(APP_PATH . 'common/constant.php');
//        if (empty($GLOBALS['_CFG'])) {
//            //获取系统参数信息
//            $this->_CFG = $GLOBALS['_CFG'] = model('ShopConfig')->loadShopConfigs();
//        }
//        $this->assign('cfg',$this->_CFG);
       if ( input('page_size/d') > 0) {
           $this->pageSize = input('page_size/d');
           session('page_size',$this->pageSize);
       }elseif(session('page_size')){
           $this->pageSize = session('page_size');
       }
    }
    
    public function _initialize() {
        parent::_initialize();
        $this->assign('lang',$GLOBALS['_LANG']);
    }
    
    public function _empty(){
        return $this->fetch('../../404');
    }
    
    /**
     * 页面跳转
     */
    public function url_redirect($message = '', $url = '', $text = '', $time = 3, $type = '') {
        $url = htmlspecialchars_decode($url);
        if (is_array($url)) {
            $link = $url;
        } else {
            $link[0]['url'] = urldecode($url);
            $link[0]['text'] = "返回" . $text;
        }
        $data['url'] = $link[0]['url'];
        $data['message'] = $message;
        $data['time'] = urldecode($time);
        $data['type'] = urldecode($type);
//        if (empty($data['url'])) {
//            $data['url'] = $_SERVER['HTTP_REFERER'];
//        }
        $this->assign('data', $data);
        $this->assign('link', $link);
        return $this->fetch('public/url_redirect');
        die;
    }
}
