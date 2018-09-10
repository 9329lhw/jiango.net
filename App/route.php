<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;

Route::rule([
    'activity' => 'index/index/index',
    'goods' => 'index/index/index',
    'draw' => 'index/index/index',
    'drawRule' => 'index/index/show',
    'prize' => 'index/index/show',
    'person' => 'index/index/index',
    'search' => 'index/index/index',
    'searchGoods' => 'index/index/index',
    'orders' => 'index/index/index',
    'shareRule' => 'index/index/show',
    'spokesman' => 'index/index/index',
    'MyfriendsU' => 'index/index/index',
    'Myfriends' => 'index/index/index',
    'friendsOrders' => 'index/index/index',//好友订单
    'integration' => 'index/index/index',
    'cash' => 'index/index/index',
    'cashRecord' => 'index/index/index',
    'cashValidate' => 'index/index/index',
    'partners' => 'index/index/index',
    'teamSpokesman' => 'index/index/index',
    'redPacket' => 'index/index/index',
    'redPacketS' => 'index/index/index',
    'lotteryDraw' => 'index/index/index',
    'money_detail' => 'index/index/index',
]);

//
//Route::group('api_andriod', [
//    'index/index' => ['api/api_andriod.Index/index'],
//    'index/getmore' => ['api/api_andriod.Index/getMore'],
//    'goods/detail' => ['api/api_andriod.Goods/detail'],
//    'user/login' => ['api/api_andriod.User/login'],
//    'user/detail' => ['api/api_andriod.User/userDetail'],
//    'draws/aplay_draws' => ['api/api_andriod.Draws/aplayDraws'],
//]);
//
//Route::group('api_ios', [
//    'index/index' => ['api/api_andriod.Index/index'],
//    'index/getmore' => ['api/api_andriod.Index/getMore'],
//    'goods/detail' => ['api/api_andriod.Goods/detail'],
//    'user/login' => ['api/api_andriod.User/login'],
//    'user/detail' => ['api/api_andriod.User/userDetail'],
//    'draws/aplay_draws' => ['api/api_andriod.Draws/aplayDraws'],
//]);
//
//
//Route::group('v1', [
//    'index/index' => ['api/api_andriod.Index/index'],
//    'index/getmore' => ['api/api_andriod.Index/getMore'],
//    'goods/detail' => ['api/api_andriod.Goods/detail'],
//    'user/login' => ['api/api_andriod.User/login'],
//    'user/detail' => ['api/api_andriod.User/userDetail'],
//    'draws/aplay_draws' => ['api/api_andriod.Draws/aplayDraws'],
//]);


