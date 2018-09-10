<?php
namespace app\ios\controller;
use think\Result;
use app\logic\CommontGoodsLogic;
use app\ioslogic\AdLogic;
use app\ioslogic\KeywordLogic;

class Index extends Api
{
    public function index()
    {
        $cat_list = get_category_by_type(4); //分类列表
        $ad_list = AdLogic::instance()->getAdList(); //商品列表
        $goods_info = CommontGoodsLogic::instance()->getHomeGoodsList(); //商品列表
        $prize = model('Prize')->field('prize_day,jackpot')->where(['status' => 0])->order('prize_day')->find();
        $prize = $prize?$prize->toArray():array();
        $prev_prize = model('Prize')->field('prize_day,jackpot')->where(['status' => 1])->order('prize_day desc')->find();
        $prev_prize = $prev_prize?$prev_prize->toArray():array();
        $hot_keyword = KeywordLogic::instance()->getHotKeyword();
        
        $data = [
            'hot_keyword' => $hot_keyword,
            'prize_list_url' => SERVER_PATH.'/prize?isApp=1',
            'prize_rule_url' => SERVER_PATH.'/drawRule?isApp=1',
            'category' => $cat_list,
            'cat_juhuasuan' => get_category_by_type(3)[0],
            'cat_baoyou' => get_category_by_type(3)[1],
            'cat_qianggou' => get_category_by_type(3)[2],
            'ad' => $ad_list,
            'next_page' => $goods_info['page_code'],
            'next_page_url' => '/index/getmore',
            'goods_list' => $goods_info['goods_list'],
            'prize'=>['now'=>$prize,'prev'=>$prev_prize]
        ];
        Result::instance()->success('查询成功',$data)->output();
    }
    
    public function getMore(){
        $next_page = $this->request->param('page');
        $goods_info = CommontGoodsLogic::instance()->getHomeGoodsList($next_page); //商品列表
        $data = [
            'next_page' => $goods_info['page_code'],
            'next_page_url' => '/index/getmore',
            'goods_list' => $goods_info['goods_list'],
        ];
        Result::instance()->success('查询成功',$data)->output();
    }
}
