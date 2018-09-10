<?php
namespace app\logic;
use think\Base;
use think\Result;
use think\Request;
use think\Config;
use app\common\model\DtkGoods;

class ApiGoodsLogic extends Base {
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
//                if ($num == 0) {
//                    $tb_goods_list = $this->getTbkItemNew($keyword, $page, $page_size, 'total_sales_des', $is_tmall);
//                    foreach ($tb_goods_list as $val) {
//                        $data = array();
//                        $id = model('TbGoods')->getGoodsByNumIid($val['num_iid']);
//                        if ($id) {
//                            $data['id'] = $id;
//                        }
//                        $data['num_iid'] = $val['num_iid'];
//                        $data['title'] = $val['title'];
//                        $data['intro'] = $val['intro'];
//                        $data['nick'] = $val['nick'];
//                        $data['sale_num'] = $val['sale_num'];
//                        $data['pic_url'] = $val['pic_url'];
//                        $data['price'] = $val['price'];
//                        $data['org_price'] = $val['org_price'];
//                        $result = model('TbGoods')->saveData($data);
//                    }
//                } elseif ($num <= $page_size) {
//                    $goods1 = $tb_coupon_goods_list;
//                    $diff_num = $page_size;
//                    $page = 1;
//                    $goods2 = $this->getTbkItemNew($keyword, $page, $diff_num, '', $is_tmall);
//                    if (!empty($goods2)) {
//                        foreach ($goods2 as $val) {
//                            $data2 = array();
//                            $id = model('TbGoods')->getGoodsByNumIid($val['num_iid']);
//                            if ($id) {
//                                $data2['id'] = $id;
//                            }
//                            $data2['num_iid'] = $val['num_iid'];
//                            $data2['title'] = $val['title'];
//                            $data2['intro'] = $val['intro'];
//                            $data2['nick'] = $val['nick'];
//                            $data2['sale_num'] = $val['sale_num'];
//                            $data2['pic_url'] = $val['pic_url'];
//                            $data2['price'] = $val['price'];
//                            $data2['org_price'] = $val['org_price'];
//                            $result = model('TbGoods')->saveData($data2);
//                        }
//                    }
//                    $tb_goods_list = array_merge($goods1, $goods2);
//                }else{
//                    $tb_goods_list = $tb_coupon_goods_list;
//                }
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
//            $coupon_queue_info = [
//                'pid'=>$this->pid,
//                'goods_list' => $tb_array_coupon
//            ];
//            $coupon_queue = json_encode($coupon_queue_info);
//            \lib\cache\RedisCache::instance()->lPush('tb_coupon_queue',$coupon_queue);
            
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
                    $data = array();
                    $id = model('TbCouponGoods')->getGoodsByNumIid($goods['num_iid'], $this->pid);
                    if ($id) {
                        $data['id'] = $id;
                    }
                    $data['num_iid'] = $goods['num_iid'];
                    $data['pid'] = $this->pid;
                    $data['title'] = $goods['title'];
                    $data['intro'] = $goods['intro'];
                    $data['nick'] = $goods['nick'];
                    $data['sale_num'] = $goods['sale_num'];
                    $data['pic_url'] = $goods['pic_url'];
                    $data['price'] = $goods['price'];
                    $data['org_price'] = $goods['org_price'];
                    $data['coupon_price'] = $goods['coupon_price'] ?: 0;
                    $data['item_url'] = $goods['item_url'];
                    $data['commission'] = $goods['commission'];
                    $result = model('TbCouponGoods')->saveData($data);
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

//        $level = arrayLevel($obj['n_tbk_item']);
//        $tb_array = [];
//        if($level>1){
//            $tb_array = $obj['n_tbk_item'];
//        }else{
//            $tb_array =[$obj['n_tbk_item']];
//        }
        
//        if (!empty($tb_array)){
//            $goods_queue = json_encode($tb_array);
//            \lib\cache\RedisCache::instance()->lPush('goods_queue',$goods_queue);
//        }
        $goods_list = [];
        if(!empty($tb_array)){
            foreach ($tb_array as $key=>$val){
                $goods = array();
                $goods['item_url'] = "https://uland.taobao.com/coupon/edetail?activityId=&itemId=".$val['num_iid'] . '&pid=' . $this->pid;
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
                $data = array();
                $id = model('TbGoods')->getGoodsByNumIid($goods['num_iid']);
                if ($id) {
                    $data['id'] = $id;
                }
                $data['num_iid'] = $goods['num_iid'];
                $data['title'] = $goods['title'];
                $data['intro'] = $goods['intro'];
                $data['nick'] = $goods['nick'];
                $data['sale_num'] = $goods['sale_num'];
                $data['pic_url'] = $goods['pic_url'];
                $data['price'] = $goods['price'];
                $data['org_price'] = $goods['org_price'];
                $result = model('TbGoods')->saveData($data);
            }
        }
        return $goods_list;
    }
}

