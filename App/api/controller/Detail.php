<?php

namespace app\api\controller;

use think\Result;
use app\logic\CommontGoodsLogic;
use app\common\model\EmojImg;

class Detail extends Api {

    public function info() {
        $param = geturlnew(input('param',''));
        if(empty($param)){
            Result::instance()->fail('请求参数有误')->output();
        }
        $coupon_price = input('coupon_price','0.00');
        $commission = input('commission','0.00');
        $uid = $this->request->post('user_id');
        $user = model('User')->where('uid',$uid)->find();
        $bose_rate = \lib\cache\CacheTool::configsCache('boss_agent_order_commission_rate');
        $partner_rate = \lib\cache\CacheTool::configsCache('partner_agent_order_commission_rate') + $bose_rate;
        $goods_info = CommontGoodsLogic::instance()->getGoodsInfo($param['n'], $param['p'], $param['f'], $commission, $coupon_price);
        if($param['f'] != 'hdk'){
            unset($goods_info['id']);
            unset($goods_info['pid']);
            unset($goods_info['num_iid']);
            $goods_info['copy_text'] = '';
            $goods_info['zhekou'] = number_format(($goods_info['price'] / $goods_info['org_price']) * 10,2);
            $goods_info['share_url'] = SERVER_PATH.'/goods?inviter='.$uid.'&param='.input('param','');
            $goods_info['partner_brokerage'] = empty($user->partner_agent) ? 0 : number_format($goods_info['price'] * $partner_rate * $goods_info['commission'] / 10000,2);
            $goods_info['boss_brokerage'] = empty($user->boss_agent) ? 0 : number_format($goods_info['price'] * $bose_rate * $goods_info['commission'] / 10000,2);
            $goods_info['brokerage'] = empty($goods_info['partner_brokerage']) ? (empty($goods_info['boss_brokerage']) ? 0 : $goods_info['boss_brokerage']) : $goods_info['partner_brokerage'];
        }
        
        if(isset($goods_info['fqcat'])){
            $goods_info['recomand_goods'] = CommontGoodsLogic::instance()->getRecomandGoods($goods_info['fqcat']);
            $goods_info['recomand_goods']['next_page_url'] = '/category/getGoodsList';
            $goods_info['recomand_goods']['fqcat'] = $goods_info['fqcat'];
            $goods_info['recomand_goods']['tag'] = 2;
        }else{
            $goods_info['fqcat'] = '';
            $goods_info['recomand_goods'] = [];
        }
        $goods_info['similar_info'] = CommontGoodsLogic::instance()->getSimilarInfo($param['n']);
        $goods_info['similar_info']['next_page_url'] = '/category/getSimilarGoodsList';
        Result::instance()->success('查询成功',$goods_info)->output();
    }

     public function intro(){
        $miid = input('num_iid','');
        if(empty($miid)){
            Result::instance()->fail('请求参数有误')->output();
        }
        $postData = json_encode(["item_num_id"=>$miid]);
        $url = "http://hws.m.taobao.com/cache/mtop.wdetail.getItemDescx/4.1/?data=".$postData;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        $info = json_decode($output);
        $intro_img = $info->data->images;
        if(!isset($intro_img)){
            Result::instance()->fail('暂没有详情图片')->output();
        }
        Result::instance()->success('查询成功',$intro_img)->output();
    }
    
    public function getEmojImg(){
        $url = "http://api.haodanku.com/emoji/emoji_list_api";
        $result = CommontGoodsLogic::instance()->doget($url);
        print_r($result);
    }

}
