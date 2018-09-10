<?php
namespace app\logic;
use think\Base;
use think\Result;
use think\Request;
use think\Config;
use app\common\model\DtkGoods;
use app\common\model\TbGoods;
use app\common\model\TbCouponGoods;
use app\common\model\GoodsTbPwd;

class CommontGoodsLogic extends Base {
    protected $key = 'jianggo';
    protected $partner_agent;
    protected $boss_agent;
    protected $partner_rate;
    protected $boss_rate;
    protected $uid;
    protected $pid;
    protected $request;
    protected $tbk_appkey;
    protected $tbk_secretKey;
    
    protected function __construct(Request $request = null) {
        $this->request = is_null($request) ? Request::instance() : $request;
        $this->tbk_appkey = Config::get('tbk_appkey');
        $this->tbk_secretKey = Config::get('tbk_secretKey');
        $this->getUid();
    }
    public function getUid(){
        $this->uid = $this->request->param('user_id');
        if(empty($this->uid)){
            Result::instance()->errCode(401)->fail('请登录')->output();
        }
        $user = model('User')->where('uid', $this->uid)->find();
        
        $this->partner_agent = empty($user['partner_agent']) ? '' : $user['partner_agent'];
        $this->boss_agent = empty($user['boss_agent']) ? '' : $user['boss_agent'];
        $this->boss_rate = \lib\cache\CacheTool::configsCache('boss_agent_order_commission_rate');
        $this->partner_rate = \lib\cache\CacheTool::configsCache('partner_agent_order_commission_rate') + $this->boss_rate;
        
        if ($user->boss_agent) {
            $this->pid = $user['pid'] ? $user['pid'] : SHOP_PID;
        } else {
            $boss = model('UserRelation')->alias('ur')->join('user u', 'u.uid=ur.boss_agent_uid')->where(['ur.uid' => $this->uid, 'u.boss_agent' => 1])->find();
            $this->pid = $boss['pid'] ? $boss['pid'] : '';
        }
        //缓存获取pid
        if(empty($this->pid)){
            $this->pid = session('pid');
        }
        //判断pid是否合法
        $pid = UserLogic::getPidByPid($this->pid);
        //默认为最高代理pid
        if(empty($pid)){
            $this->pid = Config::get('tbk_pid');
        }
        if($this->pid != session('pid')){
            session('pid', $this->pid, 86400);
        }
    }
    /**
     * 
     * @param type $page
     * @param type $page_size
     * @return string
     */
    public function getGoodsList($page,$page_size,$sort) {
        $order = '';
        if(!empty($sort)){
            $order .= "$sort desc";
        }
        $pre = ($page - 1) * $page_size;
        $list = DtkGoods::getList($pre,$page_size,'',$order);
        foreach ($list as $key=>$val){
            $list[$key]['item_url'] = $val['item_url'].'&pid='.$this->pid;
            $list[$key]['coupon_price'] = ($val['coupon_price'] == 0) ? '' : $val['coupon_price'];
            $list[$key]['param'] = base64_encode('n='.$val['num_iid'].'&p='.$this->pid.'&f=dtk');
            $list[$key]['share_url'] = SERVER_PATH.'/goods?inviter='.$this->uid.'&param='.$list[$key]['param'];
            $list[$key]['partner_brokerage'] = empty($this->partner_agent) ? 0 : number_format($list[$key]['price'] * $this->partner_rate * $val['commission'] / 10000,2);
            $list[$key]['boss_brokerage'] = empty($this->boss_agent) ? 0 : number_format($list[$key]['price'] * $this->boss_rate * $val['commission'] / 10000,2);
            $list[$key]['brokerage'] = empty($list[$key]['partner_brokerage']) ? (empty($list[$key]['boss_brokerage']) ? 0 : $list[$key]['boss_brokerage']) : $list[$key]['partner_brokerage'];
        }
        return $list;
    }

    /**
     * 根据分类获取商品类表
     * @param type $cate_id
     * @param type $page
     * @param type $page_size
     * @return string
     */
    public function getGoodsListByCate($cate_id, $page, $page_size,$sort) {
        //关键字搜索
        if (empty($cate_id)) {
            Result::instance()->fail('分类tag不能为空')->output();
        }
        $order = '';
        if(!empty($sort)){
            $order .= "$sort desc";
        }
        $where = " and cate_id = $cate_id";
        $pre = ($page - 1) * $page_size;
        $tb_goods_list = DtkGoods::getList($pre, $page_size, $where , $order);
        
        foreach ($tb_goods_list as $key => $val) {
            $tb_goods_list[$key]['item_url'] = $val['item_url'] . '&pid=' . $this->pid;
            $tb_goods_list[$key]['coupon_price'] = ($val['coupon_price'] == 0) ? '' : $val['coupon_price'];
            $tb_goods_list[$key]['zhekou'] = number_format(($tb_goods_list[$key]['price'] / $tb_goods_list[$key]['org_price']) * 10,2);
            $tb_goods_list[$key]['param'] = base64_encode('n='.$val['num_iid'].'&p='.$this->pid.'&f=dtk');
            $tb_goods_list[$key]['share_url'] = SERVER_PATH.'/goods?inviter='.$this->uid.'&param='.$tb_goods_list[$key]['param'];
            $tb_goods_list[$key]['partner_brokerage'] = empty($this->partner_agent) ? 0 : number_format($tb_goods_list[$key]['price'] * $this->partner_rate * $val['commission'] / 10000,2);
            $tb_goods_list[$key]['boss_brokerage'] = empty($this->boss_agent) ? 0 : number_format($tb_goods_list[$key]['price'] * $this->boss_rate * $val['commission'] / 10000,2);
            $tb_goods_list[$key]['brokerage'] = empty($tb_goods_list[$key]['partner_brokerage']) ? (empty($tb_goods_list[$key]['boss_brokerage']) ? 0 : $tb_goods_list[$key]['boss_brokerage']) : $tb_goods_list[$key]['partner_brokerage'];
        }
        return $tb_goods_list;
    }
    /**
     * 
     * @param type $keyword
     * @param int $page
     * @param type $page_size
     * @return type
     */
    public function getGoodsListByKeywordNew($keyword, $page, $page_size, $sort, $is_tmall) {
        //关键字搜索
        if (empty($keyword)) {
            Result::instance()->fail('关键字不能为空')->output();
        }
        
        //更新搜索词
        KeywordLogic::instance()->upKeyword($keyword);
        if ($sort == 'coupon' || $sort != 'sale_num') {
            if($page == 1){
                $tb_coupon_goods_list = $this->getItemCoupon($keyword,$is_tmall);
                $num = count($tb_coupon_goods_list);
                $tb_coupon_goods_list = $tb_coupon_goods_list?:array();
                $tb_goods_list = $this->getTbkItemNew($keyword, $page, $page_size, 'total_sales_des', $is_tmall);
                $tb_goods_list = array_merge($tb_coupon_goods_list, $tb_goods_list);
            }else{
                $tb_goods_list = $this->getTbkItemNew($keyword, $page, $page_size, 'total_sales_des', $is_tmall);
            }
        }else{
            $tb_goods_list = $this->getTbkItemNew($keyword, $page, $page_size, 'total_sales_des', $is_tmall);
        }
        return $tb_goods_list;
    }
    /**
     * 
     * @param type $keyword
     * @return string
     */
    public function getItemCoupon($keyword,$is_tmall = false){
        $mm_pid = explode('_', $this->pid);
        $c = new \TopClient;
        $c->appkey = $this->tbk_appkey;
        $c->secretKey = $this->tbk_secretKey;
        $req = new \TbkDgItemCouponGetRequest;
        $req->setQ("$keyword");
        $req->setPageNo("1");
        $req->setAdzoneId("{$mm_pid[3]}");
        $req->setPageSize("100");
        $resp = $c->execute($req);
        $obj = object_to_array($resp->results);
        if(empty($obj['tbk_coupon'][0])){
            unset($obj['tbk_coupon']['small_images']);
            $tb_array_coupon = [$obj['tbk_coupon']];
        }else{
            $tb_array_coupon = $obj['tbk_coupon'];
        }
        $goods_list = [];
        if(!empty($tb_array_coupon)&&!empty($tb_array_coupon[0])){
            
            foreach ($tb_array_coupon as $key=>$val){
                if(($is_tmall == 'true' || $is_tmall === true) && $val['user_type'] == 0){
                    continue;
                }
                if ($val['volume'] > 0) {
                    $coupon_price = findNum($val['coupon_info']);
                    $goods = array();
                    $goods['item_url'] = $val['coupon_click_url'];
                    $goods['commission'] = $val['commission_rate'];
                    $goods['num_iid'] = $val['num_iid'];
                    $goods['title'] = $val['title'];
                    $goods['intro'] = $val['item_description'];
                    $goods['nick'] = $val['nick'];
                    $goods['sale_num'] = $val['volume'];
                    $goods['price'] = $val['zk_final_price'] - $coupon_price;
                    $goods['org_price'] = $val['zk_final_price'];
                    $goods['pic_url'] = $val['pict_url'];
                    $goods['coupon_price'] = $coupon_price;
                    $goods['coupon_end_time'] = $val['coupon_end_time'];
                    $goods['url_type'] = ($val['user_type'] == 1) ? 2 : 1;
                    $goods['param'] = base64_encode('n='.$val['num_iid'].'&p='.$this->pid.'&f=coupon');
                    $goods['share_url'] = SERVER_PATH.'/goods?inviter='.$this->uid.'&param='.$goods['param'];
                    $goods['partner_brokerage'] = empty($this->partner_agent) ? 0 : number_format($goods['price'] * $this->partner_rate * $val['commission_rate'] / 10000,2);
                    $goods['boss_brokerage'] = empty($this->boss_agent) ? 0 : number_format($goods['price'] * $this->boss_rate * $val['commission_rate'] / 10000,2);
                    $goods['brokerage'] = empty($goods['partner_brokerage']) ? (empty($goods['boss_brokerage']) ? 0 : $goods['boss_brokerage']) : $goods['partner_brokerage'];
                    $goods_list[] = $goods;
                }
            }
        }
        return $goods_list;
    }
    /**
     * 
     * @param type $keyword
     * @param type $page
     * @param type $page_size
     * @return string
     */
    public function getTbkItemNew($keyword, $page, $page_size, $sort='', $is_tmall=''){
        $c = new \TopClient;
        $c->appkey = $this->tbk_appkey;
        $c->secretKey = $this->tbk_secretKey;
        $req = new \TbkItemGetRequest;
        $req->setFields("num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick");
        $req->setQ("$keyword");
        $req->setPageNo("$page");
        $req->setPageSize("$page_size");
        if(empty($sort)){
            $req->setSort("total_sales_des");
        }else{
            $req->setSort($sort);
        }
        if(!empty($is_tmall)){
           $req->setIsTmall("$is_tmall");
        }
        $resp = $c->execute($req);
        $obj = object_to_array($resp->results);
        
        if (empty($obj)) {
            $tb_array = [];
        } else {
            if (empty($obj['n_tbk_item'][0])) {
                unset($obj['n_tbk_item']['small_images']);
                $tb_array = [$obj['n_tbk_item']];
            } else {
                $tb_array = $obj['n_tbk_item'];
            }
        }

        $goods_list = [];
        if(!empty($tb_array)){
            foreach ($tb_array as $key=>$val){
                $goods = array();
                $goods['item_url'] = "https://uland.taobao.com/coupon/edetail?activityId=&itemId=".$val['num_iid'] . '&pid=' . $this->pid;
                $goods['commission'] = '';
                $goods['num_iid'] = $val['num_iid'];
                $goods['title'] = $val['title'];
                $goods['intro'] = '';
                $goods['nick'] = $val['nick'];
                $goods['sale_num'] = $val['volume'];
                $goods['price'] = $val['zk_final_price'];
                $goods['org_price'] = $val['reserve_price'];
                $goods['pic_url'] = $val['pict_url'];
                $goods['coupon_price'] = '';
                $goods['coupon_end_time'] = '';
                $goods['url_type'] = ($val['user_type'] == 1) ? 2 : 1;
                $goods['param'] = base64_encode('n='.$val['num_iid'].'&p='.$this->pid.'&f=global');
                $goods['share_url'] = SERVER_PATH.'/goods?inviter='.$this->uid.'&param='.$goods['param'];
                $goods['partner_brokerage'] = 0;
                $goods['boss_brokerage'] = 0;
                $goods['brokerage'] = 0;
                $goods_list[] = $goods;
            }
        }
        return $goods_list;
    }
    
    public function getGoodsInfo($num_iid, $pid, $request_from, $commission = '0.00', $coupon_price = '0.00') {
        if(empty($num_iid)){
            Result::instance()->fail('商品num_iid不能为空')->output();
        }
        if(empty($pid)){
            Result::instance()->fail('pid不能为空')->output();
        }

        if ($request_from == 'global') {
            $goodinfo = TbGoods::getGoodsInfoByNumId($num_iid);
        }elseif ($request_from == 'coupon') {
            $goodinfo = TbCouponGoods::getGoodsInfoByNumId($num_iid,$pid);
        }elseif ($request_from == 'hdk') {
            $goodinfo = $this->getHdkGoodsInfo($num_iid);
        }else{
            $goodinfo = DtkGoods::getGoodsByNumId($num_iid);
        }
        
        $info = $this->aplayWarrior($num_iid, $pid);
      
        if(empty($goodinfo)){
            $c = new \TopClient;
            $c->appkey = $this->tbk_appkey;
            $c->secretKey = $this->tbk_secretKey;
            $req = new \TbkItemInfoGetRequest;
            $req->setFields("num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick");
            $req->setNumIids("$num_iid");
            $req->setPlatform("1");
            $resp = $c->execute($req);
            $obj = object_to_array($resp->results);
            
            $goodinfo['num_iid'] = $obj['n_tbk_item']['num_iid'];
            $goodinfo['title'] = $obj['n_tbk_item']['title'];
            $goodinfo['copy_text'] = '';
            $goodinfo['nick'] = $obj['n_tbk_item']['nick'];
            $goodinfo['pic_url'] = $obj['n_tbk_item']['small_images']['string'][0];
            $goodinfo['price'] = $obj['n_tbk_item']['zk_final_price'];
            $goodinfo['org_price'] = $obj['n_tbk_item']['reserve_price'];
            $goodinfo['coupon_price'] = $coupon_price;
            $goodinfo['commission'] = $commission;
            $goodinfo['sale_num'] = $obj['n_tbk_item']['volume'];
            $goodinfo['url_type'] = ($obj['n_tbk_item']['user_type'] == 1) ? 2 : 1;
            $goodinfo['zhekou'] = number_format(($goodinfo['price'] / $goodinfo['org_price']) * 10,2);
            if($request_from == 'coupon'){
                $goodinfo['pid'] = $pid;
                TbCouponGoods::insert($goodinfo);
            }elseif ($request_from == 'global') {
                TbGoods::insert($goodinfo);
            }
        }
        
        if(!empty($info['data']['coupon_click_url'])){
            $goodinfo['item_url'] = $info['data']['coupon_click_url'];
        }else{
            $goodinfo['item_url'] = "https://uland.taobao.com/coupon/edetail?activityId=&itemId=$num_iid"."&pid=$pid";
        }
        if($request_from == 'hdk'){
            $goodinfo['item_url'] = "https://uland.taobao.com/coupon/edetail?activityId=".$goodinfo['coupon_id']."&itemId=$num_iid"."&pid=$pid";
            $goodinfo['pic_url'] = empty($goodinfo['taobao_image'])?[$goodinfo['pic_url']]:explode(',', $goodinfo['taobao_image']);
            unset($goodinfo['taobao_image']);
        }else{
            $goodinfo['pic_url'] = [$goodinfo['pic_url']];
            $goodinfo['param'] = base64_encode('n='.$goodinfo['num_iid'].'&p='.$this->pid.'&f='.$request_from);
            $goodinfo['url_type'] = 1;
        }
        
        
        return $goodinfo;
    }
    
    /**
     * @param $miid
     * @param $pid
     * @return mixed
     */
    public function aplayWarrior($miid, $pid){
        $url = 'http://v2.api.haodanku.com/ratesurl';
        $postData = [
            'apikey' => 'jianggo',
            'itemid' => $miid,
            'pid' => $pid
        ];
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$postData);
        $return = curl_exec($ch);
        curl_close($ch);
        return json_decode($return,true);
    }
    
    
    public function doget($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $notice = curl_exec($ch);
        return json_decode($notice,true);
    }
    
    //------------------------------------------2018-07-05--------------------------------------
    /**
     * 获取好单库分类商品
     * @param type $type
     * @param type $page_code
     * @param type $page_size
     * @param type $sort
     * @return type
     */
    public function getGoodsListByColumn($type,$page_code,$page_size,$sort){
        
        if(empty($type)){
            Result::instance()->fail('商品类型不能为空')->output();
        }
        
        $url = "http://v2.api.haodanku.com/column/apikey/jianggo/type/$type/back/$page_size/min_id/$page_code/sort/$sort";
        $result = $this->doget($url);
//        print_r($result['data']);die;
        $goods_list = [];
        $next_page_code = '';
        if($result['code']==1 && $result['msg']=='SUCCESS' && !empty($result['data'])){
            foreach ($result['data'] as $k=>$val){
                $goods_list[$k]['num_iid'] = $val['itemid'];
                $goods_list[$k]['nick'] = $val['sellernick'];
                $goods_list[$k]['title'] = $val['itemtitle'];
                $goods_list[$k]['copy_text'] = $val['itemshorttitle'];
                $goods_list[$k]['text'] = $val['itemdesc'];
                $goods_list[$k]['sale_num'] = $val['itemsale'];
                $goods_list[$k]['price'] = number_format($val['itemendprice'],2);
                $goods_list[$k]['org_price'] = number_format($val['itemprice'],2);
                $goods_list[$k]['pic_url'] = $val['itempic'];
                $goods_list[$k]['coupon_price'] = number_format($val['couponmoney'],2);
                $goods_list[$k]['coupon_start_time'] = date('Y-m-d',$val['couponstarttime']);
                $goods_list[$k]['coupon_end_time'] = date('Y-m-d',$val['couponendtime']);
                $goods_list[$k]['url_type'] = ($val['shoptype'] == 'B') ? 1 : 2;
                $goods_list[$k]['zhekou'] = number_format(($goods_list[$k]['price'] / $goods_list[$k]['org_price']) * 10,2);
                $goods_list[$k]['coupon_id'] = $val['activityid'];
                $goods_list[$k]['param'] = base64_encode('n='.$val['itemid'].'&p='.$this->pid.'&f=hdk');
                $goods_list[$k]['share_url'] = SERVER_PATH.'/goods?inviter='.$this->uid.'&param=' . $goods_list[$k]['param'];
                $goods_list[$k]['partner_brokerage'] = empty($this->partner_agent) ? 0 : number_format($goods_list[$k]['price'] * $this->partner_rate * $val['tkrates'] / 10000,2);
                $goods_list[$k]['boss_brokerage'] = empty($this->boss_agent) ? 0 : number_format($goods_list[$k]['price'] * $this->boss_rate * $val['tkrates'] / 10000,2);
                $goods_list[$k]['brokerage'] = empty($goods_list[$k]['partner_brokerage']) ? (empty($goods_list[$k]['boss_brokerage']) ? 0 : $goods_list[$k]['tkrates']) : $goods_list[$k]['partner_brokerage'];
            }
            
            $next_page_code = $result['min_id'];
        }
        
        return ['goods_list'=>$goods_list,'page_code'=>$next_page_code];
    }
    
    /**
     * 
     * @return type
     */
    public function getHomeGoodsList($page_code=1, $page_size=10){
        
        $url = "http://v2.api.haodanku.com/excellent_editor/apikey/jianggo/back/$page_size/min_id/$page_code";
        $result = $this->doget($url);
        $goods_list = [];
        $next_page_code = '';
        if($result['code']==1 && $result['msg']=='SUCCESS' && !empty($result['data'])){
            foreach ($result['data'] as $k=>$val){
                $goods_list[$k]['num_iid'] = $val['itemid'];
                $goods_list[$k]['title'] = preg_replace("/<(\/?br.*?)>/si","",htmlspecialchars_decode($val['itemshorttitle']));
                $goods_list[$k]['text'] = preg_replace("/<(\/?br.*?)>/si","",htmlspecialchars_decode($val['text']));
                $goods_list[$k]['copy_text'] = preg_replace("/<(\/?br.*?)>/si","",htmlspecialchars_decode($val['copy_text']));;
                $goods_list[$k]['price'] = number_format($val['itemendprice'],2);
                $goods_list[$k]['org_price'] = number_format($val['itemprice'],2);
                $goods_list[$k]['pic_url'] = $val['itempic'];
                $goods_list[$k]['coupon_price'] = number_format($val['couponmoney'],2);
                $goods_list[$k]['coupon_start_time'] = date('Y-m-d H:i:s',$val['time']);
                $goods_list[$k]['url_type'] = ($val['shoptype'] == 'B') ? 1 : 2;
                $goods_list[$k]['zhekou'] = number_format(($goods_list[$k]['price'] / $goods_list[$k]['org_price']) * 10,2);
                $goods_list[$k]['coupon_id'] = getLinkParam($val['couponurl'])[activityId];
                $goods_list[$k]['param'] = base64_encode('n='.$val['itemid'].'&p='.$this->pid.'&f=hdk');
                $goods_list[$k]['share_url'] = SERVER_PATH.'/goods?inviter='.$this->uid.'&param=' . $goods_list[$k]['param'];
                $goods_list[$k]['partner_brokerage'] = empty($this->partner_agent) ? 0 : number_format($goods_list[$k]['price'] * $this->partner_rate * $val['tkrates'] / 10000,2);
                $goods_list[$k]['boss_brokerage'] = empty($this->boss_agent) ? 0 : number_format($goods_list[$k]['price'] * $this->boss_rate * $val['tkrates'] / 10000,2);
                $goods_list[$k]['brokerage'] = empty($goods_list[$k]['partner_brokerage']) ? (empty($goods_list[$k]['boss_brokerage']) ? 0 : $goods_list[$k]['boss_brokerage']) : $goods_list[$k]['partner_brokerage'];
            }
            $next_page_code = $result['min_id'];
        }
        
        return ['goods_list'=>$goods_list,'page_code'=>$next_page_code];
    }
    
    public function getHdkGoodsInfo($itemid){
        
        if(empty($itemid)){
            Result::instance()->fail('商品id不能为空')->output();
        }
        
        $url = "http://v2.api.haodanku.com/item_detail/apikey/jianggo/itemid/$itemid";
        $result = $this->doget($url);
//        print_r($result);die;
        $goods_info = [];
        if($result['code']==1 && $result['msg']=='SUCCESS' && !empty($result['data'])){
            $goods_info['num_iid'] = $result['data']['itemid'];
            $goods_info['title'] = $result['data']['itemtitle'];
            $goods_info['copy_text'] = $result['data']['itemdesc'];
            $goods_info['nick'] = $result['data']['sellernick'];
            $goods_info['fqcat'] = $result['data']['fqcat'];
            $goods_info['sale_num'] = $result['data']['itemsale'];
            $goods_info['price'] = number_format($result['data']['itemendprice'], 2);
            $goods_info['org_price'] = number_format($result['data']['itemprice'], 2);
            $goods_info['pic_url'] = $result['data']['itempic'];
            $goods_info['taobao_image'] = $result['data']['taobao_image'];
            $goods_info['coupon_price'] = number_format($result['data']['couponmoney'], 2);
            $goods_info['coupon_id'] = $result['data']['activityid'];
            $goods_info['coupon_start_time'] = date('Y-m-d', $result['data']['couponstarttime']);
            $goods_info['coupon_end_time'] = date('Y-m-d', $result['data']['couponendtime']);
            $goods_info['url_type'] = ($result['data'] == 'B') ? 1 : 2;
            $goods_info['zhekou'] = number_format(($goods_info['price'] / $goods_info['org_price']) * 10,2);
            $goods_info['param'] = base64_encode('n=' . $result['data']['itemid'] . '&p=' . $this->pid . '&f=hdk');
            $goods_info['share_url'] = SERVER_PATH . '/goods?inviter=' . $this->uid . '&param=' . $goods_info['param'];
            $goods_info['partner_brokerage'] = empty($this->partner_agent) ? 0 : number_format($goods_info['price'] * $this->partner_rate * $result['data']['tkrates'] / 10000, 2);
            $goods_info['boss_brokerage'] = empty($this->boss_agent) ? 0 : number_format($goods_info['price'] * $this->boss_rate * $result['data']['tkrates'] / 10000, 2);
            $goods_info['brokerage'] = empty($goods_info['partner_brokerage']) ? (empty($goods_info['boss_brokerage']) ? 0 : $goods_info['tkrates']) : $goods_info['partner_brokerage'];
        }
        return $goods_info;
    }
    
    public function getGoodsListByHdk($nav,$cid, $page_size, $page_code, $sort){
        
        if(empty($nav)){
            Result::instance()->fail('商品类型不能为空')->output();
        }
        
        $url = "http://v2.api.haodanku.com/itemlist/apikey/jianggo/nav/$nav/cid/$cid/back/$page_size/min_id/$page_code/sort/$sort";
        $result = $this->doget($url);
//        print_r($result['data']);die;
        $goods_list = [];
        $next_page_code = '';
        if($result['code']==1 && $result['msg']=='SUCCESS' && !empty($result['data'])){
            foreach ($result['data'] as $k=>$val){
                $goods_list[$k]['num_iid'] = $val['itemid'];
                $goods_list[$k]['nick'] = $val['sellernick'];
                $goods_list[$k]['title'] = $val['itemtitle'];
                $goods_list[$k]['copy_text'] = $val['itemshorttitle'];
                $goods_list[$k]['text'] = $val['itemdesc'];
                $goods_list[$k]['sale_num'] = $val['itemsale'];
                $goods_list[$k]['price'] = number_format($val['itemendprice'],2);
                $goods_list[$k]['org_price'] = number_format($val['itemprice'],2);
                $goods_list[$k]['pic_url'] = $val['itempic'];
                $goods_list[$k]['coupon_price'] = number_format($val['couponmoney'],2);
                $goods_list[$k]['coupon_start_time'] = date('Y-m-d',$val['couponstarttime']);
                $goods_list[$k]['coupon_end_time'] = date('Y-m-d',$val['couponendtime']);
                $goods_list[$k]['url_type'] = ($val['shoptype'] == 'B') ? 1 : 2;
                $goods_list[$k]['zhekou'] = number_format(($goods_list[$k]['price'] / $goods_list[$k]['org_price']) * 10,2);
                $goods_list[$k]['coupon_id'] = $val['activityid'];
                $goods_list[$k]['param'] = base64_encode('n='.$val['itemid'].'&p='.$this->pid.'&f=hdk');
                $goods_list[$k]['share_url'] = SERVER_PATH.'/goods?inviter='.$this->uid.'&param=' . $goods_list[$k]['param'];
                $goods_list[$k]['partner_brokerage'] = empty($this->partner_agent) ? 0 : number_format($goods_list[$k]['price'] * $this->partner_rate * $val['tkrates'] / 10000,2);
                $goods_list[$k]['boss_brokerage'] = empty($this->boss_agent) ? 0 : number_format($goods_list[$k]['price'] * $this->boss_rate * $val['tkrates'] / 10000,2);
                $goods_list[$k]['brokerage'] = empty($goods_list[$k]['partner_brokerage']) ? (empty($goods_list[$k]['boss_brokerage']) ? 0 : $goods_list[$k]['tkrates']) : $goods_list[$k]['partner_brokerage'];
            }
            
            $next_page_code = $result['min_id'];
        }
        
        return ['goods_list'=>$goods_list,'page_code'=>$next_page_code];
    }
    
    //------------------------------------------2018-07-03--------------------------------------
    /**
     * 获取同款
     * @param type $fqcat
     * @return type
     */
    public function getRecomandGoods($fqcat){
        return $this->getGoodsListByHdk(2, $fqcat, 10, 1, 0);
    }
    
    //------------------------------------------2018-07-29--------------------------------------
    /**
     * 好货专场API (新)
     */
    public function subjectHot($page_code){
        $url = "http://v2.api.haodanku.com/subject_hot/apikey/jianggo/min_id/$page_code";
        $result = $this->doget($url);
//        print_r($result);die;
        $goods_list = [];
        if($result['code']==1 && $result['msg']=='SUCCESS' && !empty($result['data'])){
            foreach ($result['data'] as $k=>$val){
                $goods_list[$k]['name']=$val['name'];
                $goods_list[$k]['share_times']=$val['share_times'];
                $goods_list[$k]['avatar']=$val['app_hot_image'];
                $goods_list[$k]['content']=htmlspecialchars_decode($val['content']);
                $goods_list[$k]['show_text']=htmlspecialchars_decode($val['show_text']);
                $goods_list[$k]['copy_text']= htmlspecialchars_decode($val['copy_text']);
                
                foreach ($val['item_data'] as $key =>$goods){
                    $g[$key]['num_iid'] = $goods['itemid'];
                    $g[$key]['title'] = $goods['itemtitle'];
                    $g[$key]['pic_url'] = $goods['itempic'];
                    $g[$key]['price'] = $goods['itemendprice'];
                    $g[$key]['org_price'] = $goods['itemprice'];
					$g[$key]['coupon_price'] = $goods['couponmoney'];
                    $g[$key]['is_expire'] = ($goods['couponstarttime'] <= time() && $goods['couponendtime'] >= time()) ? 1 : 0;
                    $g[$key]['url_type'] = ($val['shoptype'] == 'B') ? 1 : 2;
                    $g[$key]['param'] = base64_encode('n=' . $goods['itemid'] . '&p=' . $this->pid . '&f=hdk');
                    $g[$key]['share_url'] = SERVER_PATH . '/goods?inviter=' . $this->uid . '&param=' . $g[$key]['param'];
                    $coupon_id = getLinkParam($val['couponurl'])[activityId];
                    $g[$key]['jump_url'] = "https://uland.taobao.com/coupon/edetail?activityId={$coupon_id}&itemId=".$val['itemid']."&pid=".$this->pid;
                }
                $goods_list[$k]['goods_list']=$g;
            }
            if($page_code == $result['min_id']){
                $goods_list = [];
            }else{
                $next_page_code = $result['min_id'];
            }
        }
        return ['goods_list'=>$goods_list,'page_code'=>$next_page_code];
    }
    
     /**
     * 精选单品API (新)
     */
    public function selectedItem($page_code){
        $url = "http://v2.api.haodanku.com/selected_item/apikey/jianggo/min_id/$page_code";
        $result = $this->doget($url);
        $goods_list = [];
        if($result['code']==1 && $result['msg']=='SUCCESS' && !empty($result['data'])){
            foreach ($result['data'] as $k=>$val){
                $goods_list[$k]['num_iid'] = $val['itemid'];
                $goods_list[$k]['title'] = $val['title'];
                $goods_list[$k]['pic_url'] = $val['itempic'];
		$goods_list[$k]['share_times']=$val['dummy_click_statistics'];
                $goods_list[$k]['coupon_price'] = $val['couponmoney'];
                $goods_list[$k]['price'] = $val['itemendprice'];
                $goods_list[$k]['org_price'] = $val['itemprice'];
                $goods_list[$k]['content'] = htmlspecialchars_decode($val['content']);
                $goods_list[$k]['copy_content'] = htmlspecialchars_decode($val['copy_content']);
                $goods_list[$k]['comment'] = htmlspecialchars_decode($val['comment']);
                $goods_list[$k]['copy_comment'] = htmlspecialchars_decode($val['copy_comment']);
                $goods_list[$k]['param'] = base64_encode('n='.$val['itemid'].'&p='.$this->pid.'&f=hdk');
                $goods_list[$k]['share_url'] = SERVER_PATH.'/goods?inviter='.$this->uid.'&param=' . $goods_list[$k]['param'];
                $coupon_id = getLinkParam($val['couponurl'])[activityId];
                $goods_list[$k]['jump_url'] = "https://uland.taobao.com/coupon/edetail?activityId={$coupon_id}&itemId=".$val['itemid']."&pid=".$this->pid;
                
                //获取口令
                $tbpwd = GoodsTbPwd::getGoodsTbPwd($val['itemid'], $this->pid);
                if (empty($tbpwd)) {
                    $tbpwd['tbpwd'] = $this->getTbkTpwd($val['title'], $val['itempic'][0], $goods_list[$k]['jump_url'], $this->pid, $val['itemid'], $coupon_id, 'insert');
                } else {
                    if ((!empty($coupon_id) && $tbpwd['coupon_id'] != $coupon_id) || empty($tbpwd['url'])) {
                        $tbpwd['tbpwd'] = $this->getTbkTpwd($val['title'], $val['itempic'][0], $goods_list[$k]['jump_url'], $this->pid, $val['itemid'], $coupon_id, 'update');
                    }
                }
                $goods_list[$k]['comment'] = str_replace('$淘口令$', $tbpwd['tbpwd'],$goods_list[$k]['comment']);
                $goods_list[$k]['copy_comment'] = str_replace('$淘口令$', $tbpwd['tbpwd'],$goods_list[$k]['copy_comment']);
            }
            if($page_code == $result['min_id']){
                $goods_list = [];
            }else{
                $next_page_code = $result['min_id'];
            }
        }
        
        return ['goods_list'=>$goods_list,'page_code'=>$next_page_code];
    }
    
    //------------------------------------------2018-07-03--------------------------------------
    /**
     * 猜你喜欢
     * @param type $fqcat
     * @return type
     */
    public function getSimilarInfo($itemid,$min_id = 1){
        $url = "http://v2.api.haodanku.com/get_similar_info/apikey/jianggo/itemid/$itemid/min_id/$min_id";
        $result = $this->doget($url);
        $goods_list = [];
        $next_page_code = '';
        if($result['code']==1 && $result['msg']=='SUCCESS' && !empty($result['data'])){
            foreach ($result['data'] as $k=>$val){
                if($k > 3)
                break;
                $goods_list[$k]['num_iid'] = $val['itemid'];
                $goods_list[$k]['nick'] = $val['sellernick'];
                $goods_list[$k]['title'] = $val['itemtitle'];
                $goods_list[$k]['copy_text'] = $val['itemshorttitle'];
                $goods_list[$k]['text'] = $val['itemdesc'];
                $goods_list[$k]['sale_num'] = $val['itemsale'];
                $goods_list[$k]['price'] = number_format($val['itemendprice'],2);
                $goods_list[$k]['org_price'] = number_format($val['itemprice'],2);
                $goods_list[$k]['pic_url'] = $val['itempic'];
                $goods_list[$k]['coupon_price'] = number_format($val['couponmoney'],2);
                $goods_list[$k]['coupon_start_time'] = date('Y-m-d',$val['couponstarttime']);
                $goods_list[$k]['coupon_end_time'] = date('Y-m-d',$val['couponendtime']);
                $goods_list[$k]['url_type'] = ($val['shoptype'] == 'B') ? 1 : 2;
                $goods_list[$k]['zhekou'] = number_format(($goods_list[$k]['price'] / $goods_list[$k]['org_price']) * 10,2);
                $goods_list[$k]['coupon_id'] = $val['activityid'];
                $goods_list[$k]['param'] = base64_encode('n='.$val['itemid'].'&p='.$this->pid.'&f=hdk');
                $goods_list[$k]['share_url'] = SERVER_PATH.'/goods?inviter='.$this->uid.'&param=' . $goods_list[$k]['param'];
                $goods_list[$k]['partner_brokerage'] = empty($this->partner_agent) ? 0 : number_format($goods_list[$k]['price'] * $this->partner_rate * $val['tkrates'] / 10000,2);
                $goods_list[$k]['boss_brokerage'] = empty($this->boss_agent) ? 0 : number_format($goods_list[$k]['price'] * $this->boss_rate * $val['tkrates'] / 10000,2);
                $goods_list[$k]['brokerage'] = empty($goods_list[$k]['partner_brokerage']) ? (empty($goods_list[$k]['boss_brokerage']) ? 0 : $goods_list[$k]['tkrates']) : $goods_list[$k]['partner_brokerage'];
            }
            
            $next_page_code = $result['min_id'];
        }
        
        return ['goods_list'=>$goods_list,'page_code'=>$next_page_code];
    }
    
    /**
     * 
     * @param type $title
     * @param type $pic_url
     * @param type $url
     * @param type $pid
     * @param type $num_iid
     * @param type $coupon_id
     * @param type $action
     * @return type
     */
    public function getTbkTpwd($title, $pic_url, $url, $pid, $num_iid, $coupon_id, $action) {
        $c = new \TopClient;
        $c->appkey = $this->tbk_appkey;
        $c->secretKey = $this->tbk_secretKey;
        $req = new \TbkTpwdCreateRequest;
        $req->setText($title);
        $req->setUrl($url);
        $req->setLogo($pic_url);
        $req->setExt("{}");
        $resp = $c->execute($req);
        $obj = object_to_array($resp->data);
        
        $tbpwd = [
            'num_iid' => $num_iid,
            'pid' => $pid,
            'coupon_id' => $coupon_id,
            'url' => $url,
            'tbpwd' => $obj['model'],
            'update_time' => time(),
        ];
        
        if($action=='insert'){
           GoodsTbPwd::insert($tbpwd); 
        }else{
           GoodsTbPwd::where(['num_iid' => $num_iid,'pid' => $pid])->update(['coupon_id' => $coupon_id,'tbpwd' => $obj['model'], 'url'=>$url, 'update_time'=>time()]);
        }
        
        return $obj['model']; 
    }
}