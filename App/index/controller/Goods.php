<?php
namespace app\index\controller;
use think\Result;
use app\logic\GoodsLogic;


class Goods extends Base
{
    public function detail()
    {
        header('Content-Type:application/json; charset=utf-8');
        $param = geturlnew(input('param',''));
        if(empty($param)){
            Result::instance()->fail('请求参数有误')->output();
        }
        $this->uid = session('uid');
        $user = model('User')->where('uid',$this->uid)->find();
        $bose_rate = \lib\cache\CacheTool::configsCache('boss_agent_order_commission_rate');
        $partner_rate = $bose_rate + \lib\cache\CacheTool::configsCache('partner_agent_order_commission_rate');
        $goods_info = GoodsLogic::instance()->getGoodsInfoNew($param['n'],$param['p'],$param['f']);
        print_r();
        if($param['p'] != 'hdk'){
            $goods_info['share_url'] = SERVER_PATH.'/goods?inviter='.$this->uid.'&param='.input('param','');
            $goods_info['partner_brokerage'] = empty($user->partner_agent) ? 0 : number_format($goods_info['price'] * $partner_rate * $goods_info['commission'] / 10000,2);
            $goods_info['boss_brokerage'] = empty($user->boss_agent) ? 0 : number_format($goods_info['price'] * $bose_rate * $goods_info['commission'] / 10000,2);
            $goods_info['brokerage'] = empty($goods_info['partner_brokerage']) ? (empty($goods_info['boss_brokerage']) ? 0 : $goods_info['boss_brokerage']) : $goods_info['partner_brokerage'];
        }
        
        if(isset($goods_info['fqcat'])){
            $goods_info['recomand_goods'] = GoodsLogic::instance()->getRecomandGoods($goods_info['fqcat']);
            $goods_info['recomand_goods']['next_page'] = $goods_info['recomand_goods']['page_code'];
            $goods_info['recomand_goods']['next_page_url'] = '/category/getGoodsList';
            $goods_info['recomand_goods']['fqcat'] = $goods_info['fqcat'];
            $goods_info['recomand_goods']['tag'] = 2;
        }
        
        $goods_info['similar_info'] = GoodsLogic::instance()->getSimilarInfo($param['n']);
        $goods_info['similar_info']['next_page_url'] = '/category/getSimilarGoodsList';
        
        $wechatShare = $this->wechatShare;
        $wechatShare['shareTitle'] = $goods_info['title'];
        $wechatShare['shareDesc'] = "奖购-用购物的钱来中奖";
        $wechatShare['shareImg'] = $goods_info['pic_url'][0];
        $goods_info['wechat_share'] = $wechatShare;
        Result::instance()->success('查询成功',$goods_info)->output();
    }

    public function detailnew()
    {
        header('Content-Type:application/json; charset=utf-8');
        $param = geturlnew(input('param',''));
        if(empty($param)){
            Result::instance()->fail('请求参数有误')->output();
        }
        print_r($param);
        $goods_info = GoodsLogic::instance()->aplayWarrior($param['n'],$param['p']);
        print_r($goods_info);
    }
}
