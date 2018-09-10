<?php

namespace app\tebiedemingzi\controller;

class Collect extends Base
{
    protected $collect_type = [
        '1'=>['type'=> 1,'url'=>"http://www.dataoke.com/qlist/?px=zx",'name'=>'大淘客'],
        '2'=>['type'=> 2,'url'=>"http://taomaoxia.liuhw.cn/index.php?r=l&cid=0&s=latest",'name'=>'淘猫侠']
    ];
    public function _initialize() {
        parent::_initialize();
        $this->assign('lang', $GLOBALS['_LANG']);
    }
    
    public function index()
    {
        $this->assign('collect_list', $this->collect_type);
        $this->assign('ur_here', '商品采集');
        return $this->display();
    }
    
    public function collect()
    {
       $this->collect_tmx_connet($this->collect_type[2]['url']);
       
    }
    
    public function collect_dtk_goods($url){
        set_time_limit(0); 
        $cont = doCollectAjax($url);
        $pattern = "/<div id=\"goods-items_(.*)<div class=\"goods-author\">/iUs";
        preg_match_all($pattern, $cont, $matches);
        $goods_info = [];
        
        foreach ($matches[0] as $rel){
            $pattern1 = "/<span class=\"price theme-color-8\".*?>.*?<\/b>/ism";
            preg_match($pattern1,$rel,$match1); 
            
            $pattern2 = "/<span class=\"old-price\".*?>.*?<\/span>/ism";
            preg_match($pattern2,$rel,$match2); 
            
            $pattern3 = "/<span class=\"coupon theme-bg-color-9 theme-color-1 theme-border-color-1\".*?>.*?<\/b>/ism";
            preg_match($pattern3,$rel,$match3);
            
            $pattern4 = '/(src)=("[^"]*")/ism';
            preg_match($pattern4,$rel,$match4);
        }
    }
    
    public function collect_dtk_goods_info($url){
        $cont = doCollectAjax($url);
        $pattern = "/<div class=\"ehy-normal theme-bg-color-8 clearfix\".*?>.*?<div class=\"goods-tit-type\">/ism";
        preg_match_all($pattern, $cont, $matches);
        preg_match("/href=\"(.*)\" /",$matches[0][0],$match); 
        $url = str_replace('" target="_blank', '', $match[1]);
        $link = substr($url,strpos($url, '?')+1);
        $link_arr = explode('&', $link);
        $params = [];
        foreach ($link_arr as $param) {
           $item = explode('=', $param);
           $params[$item[0]] = $item[1];
        }
        return json_encode($params);
    }
    
    public function collect_tmx_connet($url){
        set_time_limit(0); 
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_TIMEOUT, 1000);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $cont = curl_exec($ch);
        curl_close($ch);
        $pattern = "/<li class=\"theme-hover-border-color-1  g_over\">(.*)<\/li>/iUs";
        preg_match_all($pattern, $cont, $matches);
        $actionobj = model('DtkGoods');
        foreach ($matches[0] as $key=>$rel){
            $pattern1 = "/<span class=\"price theme-color-8\".*?>.*?<\/b>/ism";
            preg_match($pattern1,$rel,$match1); 
            
            $pattern2 = "/<span class=\"old-price\".*?>.*?<\/span>/ism";
            preg_match($pattern2,$rel,$match2); 
            
            $pattern3 = "/<span class=\"coupon theme-bg-color-9 theme-color-1 theme-border-color-1\".*?>.*?<\/b>/ism";
            preg_match($pattern3,$rel,$match3);
            
            $pattern4 = '/(src)=("[^"]*")/ism';
            preg_match($pattern4,$rel,$match4);
            
            $pattern5 = "/<div class=\"title\".*?>.*?<\/div>/ism";
            preg_match($pattern5,$rel,$match5);
            $data['price'] = str_replace(['￥','券'],'',strip_tags($match1[0]));
            $data['org_price'] = str_replace(['￥','券'],'',strip_tags($match2[0]));
            $data['coupon_price'] = str_replace(['￥','券'],'',strip_tags($match3[0]));
            $data['pic_url'] = strip_tags($match4[2]);
            $data['title'] = str_replace([""," ","\t","\n","\r"],'',strip_tags($match5[0]));
            $data['intro'] = $data['title'];
            $url = "http://taomaoxia.liuhw.cn/".str_replace('class="img" rel="nofollow" target="_blank', '', $match[1]);
            $info = $this->get_goods_info($url);
            $info = json_decode($info,true);
            $data['coupon_id'] = $info['activityId'];
            $data['num_iid'] = $info['itemId'];
            
            if (!empty($info['itemId'])) {
                $id = $actionobj->getGoodsByNumIid($info['itemId']);
                if ($id) {
                    $data['id'] = $id;
                }
                $actionobj->saveData($data);
            }
            print_r($data);
        }
        
    }
    
    
    public function get_goods_info($url){
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_TIMEOUT, 1000);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $cont = curl_exec($ch);
        curl_close($ch);
        /*$cont = file_get_contents($url);*/
        $pattern = "/<div class=\"ehy-normal theme-bg-color-8 clearfix\".*?>.*?<div class=\"goods-tit-type\">/ism";
        preg_match_all($pattern, $cont, $matches);
        preg_match("/href=\"(.*)\" /",$matches[0][0],$match); 
        $url = str_replace('" target="_blank', '', $match[1]);
        $link = substr($url,strpos($url, '?')+1);
        $link_arr = explode('&', $link);
        $params = [];
        foreach ($link_arr as $param) {
           $item = explode('=', $param);
           $params[$item[0]] = $item[1];
        }
        return json_encode($params);
    }
    
}

