<?php
namespace app\tebiedemingzi\controller;

class Index extends Base
{
    public function _initialize() {
        parent::_initialize();
        require(EXTEND_PATH.'lib/languages/admin/privilege.php');
        require(EXTEND_PATH.'lib/languages/admin/index.php');
        $this->assign('lang',$GLOBALS['_LANG']);
        $this->assign('admin_id',session('admin_id'));
    }
    
    public function index()
    {
        return $this->fetch();
    }
    
    public function top() {
        $nav = model('AdminUser')->getfieldByUserId(session('admin_id'),'nav_list');//导航栏
        $navlist = array();
        if(!empty($nav)){
            $navlist = model('AdminAction')->where(array('action_code'=>['in', $nav],'is_show'=>1))->select();
        }
        $this->assign(['nav_list'=>$navlist,'admin_id'=>session('admin_id')]);
        return $this->display();
    }
    
    public function drag() {
        return $this->display();
    }
    
    public function menu() {
        $menulist = model('AdminAction')->getMenuTree(0);
        $this->assign('menulist', $menulist);
        return $this->display();
    }

    /**
     * 起始页
     */
    public function main() {
        global $_LANG;
        $lastday = strtotime(date('Y-m-d 0:0:0',  strtotime('-1 day')));
        $this->assign('last_pay_price',db('tb_orders')->where(['status'=>['in',[12,14,3]],'create_time'=>['between',[$lastday,$lastday+86399]]])->sum('total_pay_price'));
        $this->assign('last_order_num',db('tb_orders')->where(['create_time'=>['between',[$lastday,$lastday+86399]]])->count());
        $this->assign('last_user_num',db('user')->where(['create_time'=>['between',[$lastday,$lastday+86399]]])->count());
		
        $today = strtotime(date('Y-m-d 0:0:0',time()));
        $this->assign('today_pay_price',db('tb_orders')->where(['status'=>['in',[12,14,3]],'create_time'=>['between',[$today,$today+86399]]])->sum('total_pay_price'));
        $this->assign('today_order_num',db('tb_orders')->where(['create_time'=>['between',[$today,$today+86399]]])->count());
        $this->assign('today_user_num',db('user')->where(['create_time'=>['between',[$today,$today+86399]]])->count());
        
        $pay_movements = $user_movements = $order_movements = $income_movements = $date = array();
        for($i = 30; $i>0; $i--){
            $date[] = date('Y-m-d',  strtotime("-$i days"));
            $starttime = strtotime(date('Y-m-d 0:0:0',  strtotime("-$i days")));
            $endtime = $starttime + 86399;
            $pay_movements[] = db('tb_orders')->where(['create_time'=>['between',[$starttime,$endtime]]])->sum('total_pay_price');
            $income_movements[] = db('tb_orders')->where(['create_time'=>['between',[$starttime,$endtime]]])->sum('total_forecast_income');
            $order_movements[] = db('tb_orders')->where(['create_time'=>['between',[$starttime,$endtime]]])->count();
            $user_movements[] =db('user')->where(['create_time'=>['between',[$starttime,$endtime]]])->count();
        }
        $this->assign('date',  json_encode($date));
        $this->assign('pay_movements',  json_encode($pay_movements));
        $this->assign('income_movements',  json_encode($income_movements));
        $this->assign('order_movements',  json_encode($order_movements));
        $this->assign('user_movements',  json_encode($user_movements));
        
        $this->assign('total_income',db('tb_orders')->sum('total_forecast_income'));
        $this->assign('last_income',db('tb_orders')->where(['create_time'=>['between',[$lastday,$lastday+86399]]])->sum('total_forecast_income'));
        $this->assign('today_income',db('tb_orders')->where(['create_time'=>['between',[$today,$today+86399]]])->sum('total_forecast_income'));
        
        $this->assign('total_pay_price',db('tb_orders')->where(['status'=>['in',[12,14,3]]])->sum('total_pay_price'));//交易总金额
        $this->assign('order_num',db('tb_orders')->where(['create_time'=>['gt',0]])->count());
        $this->assign('user_num',db('user')->count());
        
        //财务情况
        $total_point = db('user')->sum('total_point');
        $point = db('user')->sum('point');
        $this->assign('total_user_point',$total_point/100);//交易总金额
        $points['rebate'] = \app\common\model\UserPointLog::where(['type'=>['in',['order_rebate','order_rebate_second','order_rebate_first','failure_order_rebate','failure_order_rebate_second','failure_order_rebate_first']]])->sum('point')/100;
        $points['prize'] = \app\common\model\UserPointLog::where(['type'=>['in',['prize', 'failure_prize']]])->sum('point')/100;
        $points['invite'] = \app\common\model\UserPointLog::where(['type'=>['in',['invite_boss', 'invite_first', 'invite_second']]])->sum('point')/100;
        $points['redpack'] = \app\common\model\UserPointLog::where(['type'=>['in',['redpack']]])->sum('point')/100;
        $points['sign_in'] = \app\common\model\UserPointLog::where(['type'=>['in',['sign_in']]])->sum('point')/100;
        $points['subscribe'] = \app\common\model\UserPointLog::where(['type'=>['in',['subscribe']]])->sum('point')/100;
        $points['boss'] = db('tb_orders')->sum('boss_agent_commission')/100;
        $points['partner'] = db('tb_orders')->sum('partner_agent_commission')/100;
        $this->assign('points',$points);
        $this->assign('total_point',$point/100);
        $this->assign('draw_point',($total_point-$point)/100);
        
        /* 系统信息 */
        $db_version = db()->query("select version() as version");
        $sys_info['os']            = PHP_OS;
        $sys_info['ip']            = $_SERVER['SERVER_ADDR'];
        $sys_info['web_server']    = $_SERVER['SERVER_SOFTWARE'];
        $sys_info['php_ver']       = PHP_VERSION;
        $sys_info['mysql_ver']     = $db_version[0]['version'];
        $sys_info['zlib']          = function_exists('gzclose') ? $_LANG['yes']:$_LANG['no'];
        $sys_info['safe_mode']     = (boolean) ini_get('safe_mode') ?  $_LANG['yes']:$_LANG['no'];
        $sys_info['safe_mode_gid'] = (boolean) ini_get('safe_mode_gid') ? $_LANG['yes'] : $_LANG['no'];
        $sys_info['timezone']      = function_exists("date_default_timezone_get") ? date_default_timezone_get() : $_LANG['no_timezone'];
        $sys_info['socket']        = function_exists('fsockopen') ? $_LANG['yes'] : $_LANG['no'];

        /* 允许上传的最大文件大小 */
        $sys_info['max_filesize'] = ini_get('upload_max_filesize');
        $this->assign('sys_info', $sys_info);
        return $this->display();
    }
    
    /**
     * 登录页面
     */
    public function login() {
        $this->assign('username', cookie('username'));
//        $this->assign('password', cookie('password'));
        $this->assign('remember', cookie('remember'));
        return $this->fetch();
    }
    
    /**
     * 登录检查
     */
    public function checklogin() {
        if(!\think\Validate::token('__token__','',$_POST)){
            return $this->url_redirect('非法数据来源', url("login"), "登录页");
        }
        $adminObj = model('AdminUser');

        $userName = input('post.username', 'admin');
        $password = input('post.password', 'admin');
        if (empty($userName) || empty($password)) {
            return $this->url_redirect('用户名和密码不能为空！', url("login"), "登录页");
        } else {
            if (input('post.remember', 0) == '1') {
                cookie('username', $userName);
                cookie('remember', input('post.remember'));
            } else {
                cookie('username', null);
                cookie('remember', 0);
            }
            $loginInfo = $adminObj->checkLogin($userName, $password);
            if ($loginInfo) {
                session('admin_id', $loginInfo['user_id']);
                session('admin_name', $loginInfo['user_name']);
                session('action_list', $loginInfo['action_list']);
                $this->redirect('index');
            } else {
                setcookie('loginNum',  empty($_COOKIE['loginNum'])?1:$_COOKIE['loginNum']++,time()+3600);
                return $this->url_redirect('用户名或密码错误', url("/tebiedemingzi/Index/login"), "登录页");
            }
        }
    }
    
    /**
     * 检查验证码正确
     */
    public function check_verify() {
        $code = input('post.code');
        return captcha_check($code);
    }
    
    /**
     * 退出
     */
    public function logout() {
        session(null);
        $this->redirect('login');
    }
    
    /**
     * 设置导航栏
     */
    public function navigation(){
        $userInfo = model('AdminUser')->getByUserId(session('admin_id'));
         //获取导航条
        $navArr = (trim($userInfo['nav_list']) == '') ? array() : explode(",", $userInfo['nav_list']);
        $navlist =array();
        foreach($navArr as $akey=>$value){
            $navlist[] = model('AdminAction')->field('action_code,action_name')->where(array('action_code'=>$value,'is_show'=>1))->find()->toArray();
        }
        $menulist = model('AdminAction')->getMenuTree(0);
        $this->assign('user',$userInfo);
        $this->assign('navlist',$navlist);
        $this->assign('menulist',$menulist);
        $this->assign('ur_here', $GLOBALS['_LANG']['modif_info']);
        return $this->display();
    }
    
    /**
     * 保存个人信息
     */
    public function saveUser(){
        $userObj = model('AdminUser');
        $result = $userObj->saveUserInfo(input());
        return $this->url_redirect($result,url('/tebiedemingzi/Index/navigation'),'个人设置');
    }

}
