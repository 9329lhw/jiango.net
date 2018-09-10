<?php

namespace app\common\model;

class Prize  extends \think\Model{
    protected $createTime = false;
    protected $updateTime = false;
    
    public static function detail($day){
        $prize = self::field('prize_day,jackpot,winning_data,winning_num,status')->where(['prize_day' => $day])->find();
        $prize = $prize?$prize->toArray():array();
        $data = $prize;
        $data['winners'] = array();
        $winners = db('winner')->alias('w')->field('w.*,u.nickname')->where(['day' => $day])->join('user u', 'u.uid=w.about_id', 'left')->select();
        foreach ($winners as $k => $v) {
            $data['winners'][$k]['user_name'] = preg_replace('/(\S{0,1})[\S\s]*(\S{1})/u', '$1***$2', $v['user_name']);
            if ($v['type'] == 'order' || $v['type'] == 'virtual') {
                $data['winners'][$k]['about'] = preg_replace('/^(\d{5})\d*(\d{2})$/', '$1******$2', $v['trade_id']);
            } else {
                $data['winners'][$k]['about'] = preg_replace('/^(\S{0,1})[\S\s]*(\S{1})$/u', '$1******$2', $v['nickname']);
            }
            $data['winners'][$k]['point'] = $v['point'];
        }
        return $data;
    }
}
