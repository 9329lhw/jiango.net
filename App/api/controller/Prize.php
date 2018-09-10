<?php

namespace app\api\controller;

use think\Result;

/**
 * 奖池接口
 * @author jiang
 */
class Prize extends Api {

    /**
     * 当前奖池详情
     * @author jiang
     */
    public function index(){
        $prize = model('Prize')->field('prize_day,jackpot,winning_data,winning_num,status')->where(['status' => 0])->order('prize_day')->find();
        $prize = $prize?$prize->toArray():array();
        $prize['prize_day'] = date('n月j日',  strtotime($prize['prize_day']));
        $prev_prize = model('Prize')->field('prize_day,jackpot,winning_data,winning_num,status')->where(['status' => 1])->order('prize_day desc')->find();
        $prev_prize = $prev_prize?$prev_prize->toArray():array();
        $data['prize'] = $prize;
        $data['prev_prize'] = $prev_prize;
        Result::instance()->data($data)->success('请求成功')->output();
    }
    
    /**
     * 历史开奖
     * @author jiang
     */
    public function history(){
        $list = db('prize')->field('prize_day,jackpot,winning_data,winning_num,floor(jackpot/winning_num) as point,status')->where(['prize_day'=>['lt',date('Y-m-d',strtotime('-1 days'))]])->order('prize_day desc')->select();
        Result::instance()->data($list)->success('请求成功')->output();
    }

    /**
     * 奖池详情
     * @author jiang
     */
    public function detail() {
        $day = input('prize_day', '');
        if(!$day){
            $day = model('Prize')->where(['status' => 1])->order('prize_day desc')->value('prize_day');
        }
        $data = \lib\cache\CacheTool::prizeCache($day);
        Result::instance()->data($data)->success('请求成功')->output();
    }


}
