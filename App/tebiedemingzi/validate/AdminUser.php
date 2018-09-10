<?php
// +----------------------------------------------------------------------
// | my
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\tebiedemingzi\validate;

use think\Validate;

class AdminUser extends Validate
{
    protected $_validate = array('rule'=>array(
        array('user_name', 'require', '请输入用户名', 1, '', 1),
        array('user_name', 'require', '请输入用户名', 0),
        array('user_name', '', '用户名已存在', 0, 'unique'),
        array('password', 'require', '请输入密码', 1, '', 1),
        array('email', 'require', '请输入用户名', 0),
        array('email', '', '邮箱已注册', 0, 'unique'),
        array('pwd_confirm', 'password', '确认密码不正确', 0, 'confirm'), // 验证确认密码是否和密码一致
    ));
    
    protected $rule = [
        'user_name' => 'require|unique:admin_user',
        'password' => 'require|confirm',
        'email' => 'require|email|unique:admin_user'
    ];
    
    protected $message = [
        'user_name.require' => '用户名必须填',
        'user_name.unique' => '用户名已存在',
        'password.require' => '密码必须填',
        'password.confirm' => '两次密码不一致',
        'email.require' => '邮箱必须填',
        'email.email' => '邮箱格式错误',
        'email.unique' => '邮箱已被注册'
    ];
    
    protected $scene = [
        'add' => ['user_name','email','password'],
//        'edit' => ['user_name'=>'unique:admin_user','password'=>'confirm','email'=>'email|unique:admin_user']
        'edit' => ['user_name','email','password']
    ];

}