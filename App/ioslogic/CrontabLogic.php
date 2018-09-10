<?php

namespace app\ioslogic;

use think\Base;
use app\common\model\TbOrdersItem;

class CrontabLogic extends Base {
    
    /**
     * 完善订单商品图片
     * @author jiang
     */
    public function updateOrderPic(){
        $c = new \TopClient;
//        $c->appkey = \lib\cache\CacheTool::configsCache('tb_appkey');
//        $c->secretKey = \lib\cache\CacheTool::configsCache('tb_secret');
        $c->appkey = 24752569;
        $c->secretKey = '3f7c04861228562d7ec3882946d1e0e4';
        $items = TbOrdersItem::where(['pic_url'=>'','create_time'=>['gt',strtotime(date('Y-m-d 00:00:00'))]])->order('id')->group('num_iid')->column('num_iid');
        $num_iids = array();
        foreach($items as $v){
            $pic_url = db('tb_goods')->where('num_iid',$v)->value('pic_url');
            if($pic_url){
                TbOrdersItem::where(['num_iid'=>$v,'pic_url'=>''])->update(['pic_url'=>$pic_url]);
            }else{
                $num_iids[] = $v;
            }
        }
        $items = $num_iids;
        $i = 0;
        $num_iids = array_slice($items,$i*30,30);
        while($num_iids){
            $req = new \TbkItemInfoGetRequest;
            $req->setFields("num_iid,title,pict_url");
            $req->setNumIids(implode(',',$num_iids));
            ob_start();
            $resp = $c->execute($req);
            ob_clean();
            $resp = json_decode(json_encode($resp),true);
            if($resp['results']['n_tbk_item']){
                if($resp['results']['n_tbk_item'][0]){
                    foreach($resp['results']['n_tbk_item'] as $v){
                        TbOrdersItem::where(['num_iid'=>$v['num_iid'],'pic_url'=>''])->update(['pic_url'=>$v['pict_url']]);
                    }
                }else{
                    TbOrdersItem::where(['num_iid'=>$resp['results']['n_tbk_item']['num_iid'],'pic_url'=>''])->update(['pic_url'=>$resp['results']['n_tbk_item']['pict_url']]);
                }
            }
            $i++;
            $num_iids = array_slice($items,$i*30,30);
        }
    }
    
}

