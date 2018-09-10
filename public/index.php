<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// [ 应用入口文件 ]
// 定义应用目录
define('RUNTIME_ENVIROMENT', getenv('RUNTIME_ENVIROMENT') ? getenv('RUNTIME_ENVIROMENT') : 'product');
define('IS_LOCAL'  , 'product' != RUNTIME_ENVIROMENT); 
//define('SERVER_PATH'  , (strpos(__FILE__,'test.jianggo.net') === false?'https://jianggo.net':'https://51yuexue.com'));
define('SERVER_PATH'  ,(isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:'https').'://'.$_SERVER['SERVER_NAME']);
define('IMG_PATH'  , 'http://img.51yuexue.com'); 
define('APP_PATH', __DIR__ . '/../App/');
define('SHOP_PID', 'mm_130792044_43290663_310700359');
define('SHOP_WECHAT', '奖购');
define('CUSTOMER_SERVICE', 'zhaoquan618');
define('IS_UP_APP', false);
define('DISCOUNT_RATE', 0.2);
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
