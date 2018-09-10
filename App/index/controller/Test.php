<?php

namespace app\index\controller;

class Test extends \think\Controller
{
    private $tbkKey = '24752569';
    private $tbkSecret = '3f7c04861228562d7ec3882946d1e0e4';
    public function index()
    {
        date_default_timezone_set('Asia/Shanghai'); 

        $c = new \TopClient;
        $c->appkey = $this->tbkKey;
        $c->secretKey = $this->tbkSecret;
        $req = new \TbkItemGetRequest;
        $req->setFields("num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick");
        $req->setQ("冷酸灵直立按压牙膏极地白套装冰柠劲爽井盐爽白");
        $req->setPageSize('1');
        
//        $req = new \TbkDgItemCouponGetRequest;
//        $req->setAdzoneId("184974953");
//        $req->setQ("女装");
//        $req->setPageNo("200");
//        $req->setPageSize("20");
        
        $req = new \TbkDgItemCouponGetRequest;
        $req->setAdzoneId("184974953");
        $req->setQ("鹰君菊花茶决明子茶菊花枸杞决明子甘草茶组合型花茶叶独立袋装");
        $req->setPageNo("1");
        $resp = $c->execute($req);

        echo '<pre>';
        print_r($resp);
        
//        $req = new \TbkOrderGetRequest;
//        $req->setFields('tb_trade_parent_id,tb_trade_id,num_iid');
//        $req->setStartTime("2016-05-23 12:18:22");
//        $req->setSpan("600");
//        $req->setPageNo("1");
//        $req->setPageSize("20");
//        $resp = $c->execute($req);
//        echo '<pre>';
//        var_dump($resp);
    }
    
    public function test()
    {
        
        date_default_timezone_set('Asia/Shanghai');
        
        $c = new \TopClient;
        $c->gatewayUrl = 'https://api.taobao.com/router/rest';
        $c->appkey = '24755574';
        $c->secretKey = '887b15c7c0d98db9b29f355b40dc91ce';
        
        //授权获取code：https://oauth.taobao.com/authorize?response_type=code&client_id=24755574&redirect_uri=http://127.0.0.1:12345/error
        //获取access_token即sessionKey
//        $req = new \TopAuthTokenCreateRequest;
//        $req->setCode("bqcCY4f18K8IGpj6tGzl8aXx80894");
//        $resp = $c->execute($req);  
//        var_dump($resp);
        //更新access_token
//        $req = new \TopAuthTokenRefreshRequest;
//        $req->setRefreshToken("6201310e304bad9cd8ZZ68fdc6d701f8a3af073cc1c89263841561972"); 
//        $resp = $c->execute($req);
//        var_dump($resp);
        
        $c = new \TopClient;
        $c->appkey = '24755574';
        $c->secretKey = '887b15c7c0d98db9b29f355b40dc91ce';
        /*$req = new \TbkRebateOrderGetRequest;
        $req->setFields("tb_trade_parent_id,tb_trade_id,num_iid,item_title,item_num,price,pay_price,seller_nick,seller_shop_title,commission,commission_rate,unid,create_time,earning_time");
        $req->setStartTime("2018-01-07 13:52:08");
        $req->setSpan("600");
        $req->setPageNo("1");
        $req->setPageSize("20");
        $resp = $c->execute($req);
        print_r($resp);*/
        $req = new \TbkTpwdCreateRequest;
        $req->setText("倍思iPhone6数据线苹果6S充电线器X手机8plus加长5s六2米ipad7P");
        $req->setLogo("https://img.alicdn.com/imgextra/i2/3166992919/TB2LlTDoDnI8KJjSszgXXc8ApXa_!!3166992919.jpg");
        $req->setUrl("https://uland.taobao.com/coupon/edetail?e=4rA52AcFapIGQASttHIRqf7zaCpDP7sAStCkgxbJMujaj0PIhbeePmVyBRZtFAJiC579uq1eIwazEtHFfZ%2Fm4L9fwBwwUiqlbCZVHaAgFiNIR7InptNW9kBsXx8cnY%2FDyKftsBPO4%2FGY6yLLt5ZCZQ%3D%3D&traceId=0bfaf1b715282749892224096e&thispid=mm_130792044_43290663_312356662&src=fklm_hltk&from=tool&sight=fklm");
        $resp = $c->execute($req);
        print_r($resp);
    }
    
    
    public function item()
    {
        date_default_timezone_set('Asia/Shanghai'); 

        $c = new \TopClient;
        $c->appkey = '24755574';
        $c->secretKey = '887b15c7c0d98db9b29f355b40dc91ce';
        $req = new \TbkItemGetRequest;
        $req->setFields("num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick");
        $req->setQ("衣服");
        $req->setSort("total_sales_des");
        $req->setIsOverseas("true");
        $req->setPageNo("1");
        $req->setPageSize("1000");
        $resp = $c->execute($req);
        print_r($resp);
    }
    
    public function token(){
        $c = new \TopClient;
        $c->appkey = '24755574';
        $c->secretKey = '887b15c7c0d98db9b29f355b40dc91ce';
        $req = new \TbkTpwdCreateRequest;
        $req->setUserId("123");
        $req->setText("长度大于5个字符");
        $req->setUrl("https://uland.taobao.com/coupon/edetail?activityId=b4c52203461146ada9561528bec4956d&itemId=551532820793&pid=mm_103032786_41564092_179114865");
        $req->setLogo("https://uland.taobao.com/coupon/edetail?activityId=b4c52203461146ada9561528bec4956d&itemId=551532820793&pid=mm_103032786_41564092_179114865");
        $req->setExt("{}");
        $resp = $c->execute($req);
        print_r($resp);
    }
    
    public function url_tb(){
        $c = new \TopClient;
        $c->appkey = '24755574';
        $c->secretKey = '887b15c7c0d98db9b29f355b40dc91ce';
        $req = new \TbkItemConvertRequest;
        $req->setFields("num_iid,click_url");
        $req->setNumIids("123,456");
//        $req->setAdzoneId("123");
//        $req->setPlatform("123");
//        $req->setUnid("demo");
//        $req->setDx("1");
        $resp = $c->execute($req);
    }
    
    
    public function get_goods(){
        
        $c = new \TopClient;
        $c->appkey = '24755574';
        $c->secretKey = '887b15c7c0d98db9b29f355b40dc91ce';
        $req = new \TbkItemGetRequest;
        //num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick
        $req->setFields("num_iid,title,pict_url,reserve_price,zk_final_price,item_url,seller_id,volume");
        $req->setQ("男装");
        $req->setPageNo(1);
        $req->setPageSize(20);
        $resp = $c->execute($req);
        $obj = object_to_array($resp->results);
        print_r($obj['n_tbk_item']);
        
        foreach ($obj['n_tbk_item'] as $res){
            $url = "http://api.dataoke.com/index.php?r=port/index&appkey=70856bad5c&v=2&id=".$res['num_iid'];
            $goods = $this->httpget($url);
            print_r($goods['result']);
        }
        
        //http://api.dataoke.com/index.php?r=port/index&appkey=70856bad5c&v=2&id=xxx;
        
    }
    
    public function httpget($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $notice = curl_exec($ch);
        return json_decode($notice,true);
    }
    
    
    public function get1_goods(){
        
        $c = new \TopClient;
        $c->appkey = '24755574';
        $c->secretKey = '887b15c7c0d98db9b29f355b40dc91ce';
        $req = new \TbkDgItemCouponGetRequest;
        $req->setAdzoneId("186726422");
        $req->setPlatform("1");
        $req->setCat("16,18");
        $req->setPageSize("20");
        $req->setQ("女装");
        $req->setPageNo("1");
        $resp = $c->execute($req);
        print_r($resp);
    }
    
    public function get_counpon(){
        $c = new \TopClient;
        $c->appkey = '24755574';
        $c->secretKey = '887b15c7c0d98db9b29f355b40dc91ce';
        $req = new \TbkDgItemCouponGetRequest;
        $req->setQ("手机");
        $req->setPageNo("1");
        $req->setAdzoneId("186726422");
        $req->setPageSize("100");
        $resp = $c->execute($req);
        $obj = object_to_array($resp->results);
        $level = arrayLevel($obj['tbk_coupon']);
        $tb_array_coupon = [];
        if($level>1){
            $tb_array_coupon = $obj['tbk_coupon'];
        }else{
            $tb_array_coupon =[$obj['tbk_coupon']];
        }
        if(!empty($tb_array_coupon)){
            foreach ($tb_array_coupon as $key=>$res){
                
            }
        }
        print_r($obj);
    }
    
    public function get_connet(){
        set_time_limit(0); 
        $cont = file_get_contents("http://taomaoxia.liuhw.cn/index.php?r=l&cid=0&s=latest");
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
    
    
    public function get_goods_info(){
        $url = "http://taomaoxia.liuhw.cn/index.php?r=l/d&id=7293599&nav_wrap=l&u=800803";
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
    
    
    public function get_dtk_connet(){
        set_time_limit(0); 
        $cont = file_get_contents("http://www.dataoke.com/qlist/?px=zx");
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
    
    
    public function get_dtk_goods_info($url){
        
        $cont = file_get_contents($url);
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


    public function get_dtk(){

        echo decoct(77);
    }
}
