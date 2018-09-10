<?php
namespace app\logic;
use think\Base;
use think\Result;
use think\Request;
use think\Config;
use app\common\model\DtkGoods;
use app\common\model\TbGoods;
use app\common\model\GoodsTbPwd;
use app\common\model\TbCouponGoods;
use app\common\model\SynAplayWarriorLog;

class GoodsLogic extends Base {
    protected $key = 'jianggo';
    protected $partner_agent;
    protected $boss_agent;
    protected $partner_rate;
    protected $boss_rate;
    protected $pid;
    protected $uid;
    protected $request;
    protected $tbk_appkey;
    protected $tbk_secretKey;
    
    protected function __construct(Request $request = null) {
        $this->request = is_null($request) ? Request::instance() : $request;
        $this->tbk_appkey = Config::get('tbk_appkey');
        $this->tbk_secretKey = Config::get('tbk_secretKey');
        $this->uid = session('uid');
        $user = model('User')->where('uid',$this->uid)->find();
        
        $this->partner_agent = empty($user['partner_agent']) ? '' : $user['partner_agent'];
        $this->boss_agent = empty($user['boss_agent']) ? '' : $user['boss_agent'];
        $this->boss_rate = \lib\cache\CacheTool::configsCache('boss_agent_order_commission_rate');
        $this->partner_rate = \lib\cache\CacheTool::configsCache('partner_agent_order_commission_rate') + $this->boss_rate;
        
        if($user->boss_agent){
            $pid = $user['pid']?$user['pid']:SHOP_PID;
        }else{
            $boss = model('UserRelation')->alias('ur')->join('user u','u.uid=ur.boss_agent_uid')->where(['ur.uid'=>$this->uid,'u.boss_agent'=>1])->find();
            $pid = $boss['pid']?$boss['pid']:SHOP_PID;
        }
        session('pid',$pid);
        session('pidtime',time());
        $this->pid = session('pid');
    }
    
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
            Result::instance()->fail('分类id不能为空')->output();
        }
        $order = '';
        if(!empty($sort)){
            $order .= "$sort desc";
        }
        $where = " and cate_id = $cate_id";
        $pre = ($page - 1) * $page_size;
//        $where = " and title like '%$cate_id%'";
        $tb_goods_list = DtkGoods::getList($pre, $page_size, $where,$order);
        
        foreach ($tb_goods_list as $key => $val) {
            $tb_goods_list[$key]['item_url'] = $val['item_url'] . '&pid=' . $this->pid;
            $tb_goods_list[$key]['coupon_price'] = ($val['coupon_price'] == 0) ? '' : $val['coupon_price'];
            $tb_goods_list[$key]['param'] = base64_encode('n='.$val['num_iid'].'&p='.$this->pid.'&f=dtk');
            $tb_goods_list[$key]['share_url'] = SERVER_PATH.'/goods?inviter='.$this->uid.'&param=' . $tb_goods_list[$key]['param'];
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
     * @return type
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
        if (!empty($tb_array_coupon) && !empty($tb_array_coupon[0])) {
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
                    $goods['share_url'] = SERVER_PATH . '/goods?inviter='.$this->uid.'&param=' . $goods['param'];
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
        }else{
            $goods_list = [];
        }
        return $goods_list;
    }
    
    /**
     * 
     * @param type $keyword
     * @param type $page
     * @param type $page_size
     * @return type
     */
    public function getTbkItemNew($keyword, $page, $page_size, $sort='', $is_tmall=''){
        $c = new \TopClient;
        $c->appkey = $this->tbk_appkey;
        $c->secretKey = $this->tbk_secretKey;
        $req = new \TbkItemGetRequest;
        //num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick
        $req->setFields("num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick");
        $req->setQ("$keyword");
        $req->setPageNo("$page");
        $req->setPageSize("$page_size");
        if(empty($sort)){
            $req->setSort("total_sales_des");
        }else{
            $req->setSort($sort);
        }
        if($is_tmall){
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
//        
//        if (!empty($tb_array)){
//            $goods_queue = json_encode($tb_array);
//            \lib\cache\RedisCache::instance()->lPush('goods_queue',$goods_queue);
//        }
        $goods_list = [];
        if(!empty($tb_array)){
            foreach ($tb_array as $key=>$val){
                $goods = array();
                $goods['item_url'] = "https://uland.taobao.com/coupon/edetail?activityId=&itemId=".$val['num_iid'] . '&pid=' . $this->pid;
                $goods['commission'] = 0.00;
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
                $goods['share_url'] = SERVER_PATH . '/goods?inviter='.$this->uid.'&param=' . $goods['param'];
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
    /**
     * 
     * @param type $goodinfo
     * @return type
     */
    public function getGoodsPwd($goodinfo){
        $uid = $goodinfo['ui'];
        if(!empty($uid)){
            $boss = model('UserRelation')->alias('ur')->join('user u','u.uid=ur.boss_agent_uid')->where(['ur.uid'=>$uid,'u.boss_agent'=>1])->find();
            $pid = $boss['pid']?$boss['pid']:'';
        }
        //缓存获取pid
        if(empty($pid)){
            $pid = session('pid');
        }
        //判断pid是否合法
        $pid = UserLogic::getPidByPid($pid);
        //默认为最高代理pid
        if(empty($pid)){
            $pid = Config::get('tbk_pid');
        }
        
        $tbpwd = GoodsTbPwd::getGoodsTbPwd($goodinfo['ni'],$pid);
        $url = "https://uland.taobao.com/coupon/edetail?activityId=".$goodinfo['ci']."&itemId=".$goodinfo['ni']."&pid=".$pid;
        
        if(empty($tbpwd)){
            $tbpwd['tbpwd'] = $this->getTbkTpwd($goodinfo['t'],$goodinfo['pu'],$url,$pid,$goodinfo['ni'],$goodinfo['ci'],'insert');
        }else{
            if(!empty($goodinfo['ci']) &&($tbpwd['coupon_id'] != $goodinfo['ci'])){
                $tbpwd['tbpwd'] = $this->getTbkTpwd($goodinfo['t'],$goodinfo['pu'],$url,$pid,$goodinfo['ni'],$goodinfo['ci'],'update');
            }
        }
        return $tbpwd['tbpwd'];
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
    
    //获取商品详情
    public function getGoodsInfoNew($num_iid, $pid, $request_from) {
        if(empty($num_iid)){
            Result::instance()->fail('商品num_iid不能为空')->output();
        }
        if(empty($pid)){
            Result::instance()->fail('pid不能为空')->output();
        }

        $info = $this->aplayWarrior($num_iid, $pid);
        if($info['code'] == 0){
            $logs = SynAplayWarriorLog::where(['num_iid' => $num_iid,'pid' => $pid])->find();
            $warrior = [
                'pid' => $pid,
                'num_iid' => $num_iid,
                'msg' => $info['msg'],
                'is_syn' => 0,
                'update_time' => time()
            ];

            if(empty($logs)){
                SynAplayWarriorLog::insert($warrior);
            }else{
                SynAplayWarriorLog::where(['num_iid' => $num_iid,'pid' => $pid])->update($warrior);
            }

        }
        if ($request_from == 'global') {
            $goodinfo = TbGoods::getGoodsInfoByNumId($num_iid);
            $request = 'global';
            $url ="https://uland.taobao.com/coupon/edetail?activityId=&itemId=$num_iid"."&pid=$pid";
            $coupon_id = '';
        }elseif ($request_from == 'coupon') {
            $goodinfo = TbCouponGoods::getGoodsInfoByNumId($num_iid,$pid);
            $request = 'coupon';
            $url = $goodinfo['item_url'];
            $coupon_id = '';
        }elseif ($request_from == 'hdk') {
            $goodinfo = $this->getHdkGoodsInfo($num_iid);
            $request = 'hdk';
            $url ="https://uland.taobao.com/coupon/edetail?activityId=".$goodinfo['coupon_id']."&itemId=$num_iid"."&pid=$pid";
            $coupon_id = $goodinfo['coupon_id'];
        }else{
            $goodinfo = DtkGoods::getGoodsByNumId($num_iid);
            $request = 'dtk';
            $url ="https://uland.taobao.com/coupon/edetail?activityId=".$goodinfo['coupon_id']."&itemId=$num_iid"."&pid=$pid";
            $coupon_id = $goodinfo['coupon_id'];
        }
        if(!empty($info['data']['coupon_click_url'])){
            $url = $info['data']['coupon_click_url'];
        }
        //获取口令
        $tbpwd = GoodsTbPwd::getGoodsTbPwd($num_iid,$pid);
        if(empty($tbpwd)){
            $tbpwd['tbpwd'] = $this->getTbkTpwd($goodinfo['title'],$goodinfo['pic_url'],$url,$pid,$num_iid,$coupon_id,'insert');
        }else{
            if((!empty($coupon_id) &&$tbpwd['coupon_id'] != $coupon_id) || empty($tbpwd['url'])){
                $tbpwd['tbpwd'] = $this->getTbkTpwd($goodinfo['title'],$goodinfo['pic_url'],$url,$pid,$num_iid,$coupon_id,'update');
            }
        }
        $goodinfo['jump_url'] = $url;
        if($request_from == 'hdk'){
            $goodinfo['pic_url'] = empty($goodinfo['taobao_image'])?[$goodinfo['pic_url']]:explode(',', $goodinfo['taobao_image']);
            unset($goodinfo['taobao_image']);
        }else{
            $goodinfo['pic_url'] = [$goodinfo['pic_url']];
        }
        
        $goodinfo['tbpwd'] = $tbpwd['tbpwd'];
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
    
    public function getTop100(){
        $url = "http://api.dataoke.com/index.php?r=Port/index&type=top100&appkey=70856bad5c&v=2";
        $result = $this->doget($url);
        $goods_list = [];
        foreach ($result['result'] as $k => $val) {
            if($k > 19)
                break;
            $goods_list[$k]['item_url'] = "https://uland.taobao.com/coupon/edetail?activityId=&itemId=" . $val['Quan_id'] . "&pid=" . $val['GoodsID'];
            $goods_list[$k]['num_iid'] = $val['GoodsID'];
            $goods_list[$k]['title'] = $val['Title'];
            $goods_list[$k]['price'] = number_format($val['Price'],2);
            $goods_list[$k]['org_price'] = number_format($val['Org_Price'],2);
            $goods_list[$k]['sale_num'] =$val['Sales_num'];
            $goods_list[$k]['pic_url'] = $val['Pic'];
            $goods_list[$k]['coupon_price'] = number_format($val['Quan_price'],2);
            $goods_list[$k]['coupon_end_time'] = $val['Quan_time'];
            $goods_list[$k]['url_type'] = ($val['IsTmall'] == 1) ? 2 : 1;
            $goods_list[$k]['param'] = base64_encode('n='.$val['GoodsID'].'&p='.$this->pid.'&f=dtk');
            $goods_list[$k]['share_url'] = SERVER_PATH.'/goods?inviter='.$this->uid.'&param=' . $goods_list[$k]['param'];
            $goods_list[$k]['partner_brokerage'] = empty($this->partner_agent) ? 0 : number_format($goods_list[$k]['price'] * $this->partner_rate * $val['Commission_jihua'] / 10000,2);
            $goods_list[$k]['boss_brokerage'] = empty($this->boss_agent) ? 0 : number_format($goods_list[$k]['price'] * $this->boss_rate * $val['Commission_jihua'] / 10000,2);
            $goods_list[$k]['brokerage'] = empty($goods_list[$k]['partner_brokerage']) ? (empty($goods_list[$k]['boss_brokerage']) ? 0 : $goods_list[$k]['boss_brokerage']) : $goods_list[$k]['partner_brokerage'];
            $goods_list[$k]['Commission_jihua'] = $val['Commission_jihua'];
            $goods_list[$k]['youhui'] = number_format($goods_list[$k]['price'] * 0.3 * $val['Commission_jihua']/100,2).'~'.number_format($goods_list[$k]['price'] * 0.6 * $val['Commission_jihua']/100,2);
        }
        return $goods_list;
    }
    
    public function getPaoLiang(){
        $url = "http://api.dataoke.com/index.php?r=Port/index&type=paoliang&appkey=70856bad5c&v=2";
        $result = $this->doget($url);
        $goods_list = [];
        foreach ($result['result'] as $k => $val) {
            if($k > 19)
                break;
            $goods_list[$k]['item_url'] = "https://uland.taobao.com/coupon/edetail?activityId=&itemId=" . $val['Quan_id'] . "&pid=" . $val['GoodsID'];
            $goods_list[$k]['num_iid'] = $val['GoodsID'];
            $goods_list[$k]['title'] = $val['Title'];
            $goods_list[$k]['price'] = number_format($val['Price'],2);
            $goods_list[$k]['org_price'] = number_format($val['Org_Price'],2);
            $goods_list[$k]['sale_num'] = $val['Sales_num'];
            $goods_list[$k]['pic_url'] = $val['Pic'];
            $goods_list[$k]['coupon_price'] = number_format($val['Quan_price'],2);
            $goods_list[$k]['coupon_end_time'] = $val['Quan_time'];
            $goods_list[$k]['url_type'] = ($val['IsTmall'] == 1) ? 2 : 1;
            $goods_list[$k]['param'] = base64_encode('n='.$val['GoodsID'].'&p='.$this->pid.'&f=dtk');
            $goods_list[$k]['share_url'] = SERVER_PATH.'/goods?inviter='.$this->uid.'&param=' . $goods_list[$k]['param'];
            $goods_list[$k]['partner_brokerage'] = empty($this->partner_agent) ? 0 : number_format($goods_list[$k]['price'] * $this->partner_rate * $val['Commission_jihua'] / 10000,2);
            $goods_list[$k]['boss_brokerage'] = empty($this->boss_agent) ? 0 : number_format($goods_list[$k]['price'] * $this->boss_rate * $val['Commission_jihua'] / 10000,2);
            $goods_list[$k]['brokerage'] = empty($goods_list[$k]['partner_brokerage']) ? (empty($goods_list[$k]['boss_brokerage']) ? 0 : $goods_list[$k]['boss_brokerage']) : $goods_list[$k]['partner_brokerage'];
            $goods_list[$k]['Commission_jihua'] = $val['Commission_jihua'];
            $goods_list[$k]['youhui'] = number_format($goods_list[$k]['price'] * 0.3 * $val['Commission_jihua']/100,2).'~'.number_format($goods_list[$k]['price'] * 0.6 * $val['Commission_jihua']/100,2);
        }
        return $goods_list;
    }
    
    public function doget($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $notice = curl_exec($ch);
        return json_decode($notice,true);
    }
    
    //------------------------------------------2018-07-03--------------------------------------
    /**
     * 
     * @return type
     */
    public function getHomeGoodsList($page_size=10, $page_code=1){
        
        $url = "http://v2.api.haodanku.com/excellent_editor/apikey/jianggo/back/$page_size/min_id/$page_code";
//        echo $url;die;
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
                $goods_list[$k]['coupon_start_time'] = date('Y-m-d',$val['time']);
                $goods_list[$k]['url_type'] = ($val['shoptype'] == 'B') ? 1 : 2;
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
    
    /**
     * 获取好单库分类商品
     * @param type $type
     * @param type $page_code
     * @param type $page_size
     * @param type $sort
     * @return type
     */
    public function getGoodsListByColumn($type, $page_size, $page_code, $sort) {

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
                $goods_list[$k]['itemshorttitle'] = $val['itemshorttitle'];
                $goods_list[$k]['itemdesc'] = $val['itemdesc'];
                $goods_list[$k]['sale_num'] = $val['itemsale'];
                $goods_list[$k]['price'] = number_format($val['itemendprice'],2);
                $goods_list[$k]['org_price'] = number_format($val['itemprice'],2);
                $goods_list[$k]['pic_url'] = $val['itempic'];
                $goods_list[$k]['coupon_price'] = number_format($val['couponmoney'],2);
                $goods_list[$k]['coupon_start_time'] = date('Y-m-d',$val['couponstarttime']);
                $goods_list[$k]['coupon_end_time'] = date('Y-m-d',$val['couponendtime']);
                $goods_list[$k]['url_type'] = ($val['shoptype'] == 'B') ? 1 : 2;
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
            $goods_info['intro'] = $result['data']['itemdesc'];
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
            $goods_info['param'] = base64_encode('n=' . $result['data']['itemid'] . '&p=' . $this->pid . '&f=hdk');
            $goods_info['share_url'] = SERVER_PATH . '/goods?inviter=' . $this->uid . '&param=' . $goods_info['param'];
            $goods_info['partner_brokerage'] = empty($this->partner_agent) ? 0 : number_format($goods_info['price'] * $this->partner_rate * $result['data']['tkrates'] / 10000, 2);
            $goods_info['boss_brokerage'] = empty($this->boss_agent) ? 0 : number_format($goods_info['price'] * $this->boss_rate * $result['data']['tkrates'] / 10000, 2);
            $goods_info['brokerage'] = empty($goods_info['partner_brokerage']) ? (empty($goods_info['boss_brokerage']) ? 0 : $goods_info['tkrates']) : $goods_info['partner_brokerage'];
        }
        return $goods_info;
    }
    
    //------------------------------------------2018-07-05--------------------------------------
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
                $goods_list[$k]['itemshorttitle'] = $val['itemshorttitle'];
                $goods_list[$k]['itemdesc'] = $val['itemdesc'];
                $goods_list[$k]['sale_num'] = $val['itemsale'];
                $goods_list[$k]['price'] = number_format($val['itemendprice'],2);
                $goods_list[$k]['org_price'] = number_format($val['itemprice'],2);
                $goods_list[$k]['pic_url'] = $val['itempic'];
                $goods_list[$k]['coupon_price'] = number_format($val['couponmoney'],2);
                $goods_list[$k]['coupon_start_time'] = date('Y-m-d',$val['couponstarttime']);
                $goods_list[$k]['coupon_end_time'] = date('Y-m-d',$val['couponendtime']);
                $goods_list[$k]['url_type'] = ($val['shoptype'] == 'B') ? 1 : 2;
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
    
    //获取商品详情
    public function getGoodsDetail($num_iid, $pid, $request_from) {
        if(empty($num_iid)){
            Result::instance()->fail('商品num_iid不能为空')->output();
        }
        if(empty($pid)){
            Result::instance()->fail('pid不能为空')->output();
        }

        $info = $this->aplayWarrior($num_iid, $pid);
        if($info['code'] == 0){
            $logs = SynAplayWarriorLog::where(['num_iid' => $num_iid,'pid' => $pid])->find();
            $warrior = [
                'pid' => $pid,
                'num_iid' => $num_iid,
                'msg' => $info['msg'],
                'is_syn' => 0,
                'update_time' => time()
            ];

            if(empty($logs)){
                SynAplayWarriorLog::insert($warrior);
            }else{
                SynAplayWarriorLog::where(['num_iid' => $num_iid,'pid' => $pid])->update($warrior);
            }

        }
        
        $goodinfo = $this->getHdkGoodsInfo($num_iid);
        
        if(!empty($goodinfo)){
            $request = 'hdk';
            $url ="https://uland.taobao.com/coupon/edetail?activityId=".$goodinfo['coupon_id']."&itemId=$num_iid"."&pid=$pid";
            $coupon_id = $goodinfo['coupon_id'];
        }else{
            if ($request_from == 'global') {
                $goodinfo = TbGoods::getGoodsInfoByNumId($num_iid);
                $request = 'global';
                $url ="https://uland.taobao.com/coupon/edetail?activityId=&itemId=$num_iid"."&pid=$pid";
                $coupon_id = '';
            }elseif ($request_from == 'coupon') {
                $goodinfo = TbCouponGoods::getGoodsInfoByNumId($num_iid,$pid);
                $request = 'coupon';
                $url = $goodinfo['item_url'];
                $coupon_id = '';
            }else{
                $goodinfo = DtkGoods::getGoodsByNumId($num_iid);
                $request = 'dtk';
                $url ="https://uland.taobao.com/coupon/edetail?activityId=".$goodinfo['coupon_id']."&itemId=$num_iid"."&pid=$pid";
                $coupon_id = $goodinfo['coupon_id'];
            }
        }
        
        if(!empty($info['data']['coupon_click_url'])){
            $url = $info['data']['coupon_click_url'];
        }
        //获取口令
        $tbpwd = GoodsTbPwd::getGoodsTbPwd($num_iid,$pid);
        if(empty($tbpwd)){
            $tbpwd['tbpwd'] = $this->getTbkTpwd($goodinfo['title'],$goodinfo['pic_url'],$url,$pid,$num_iid,$coupon_id,'insert');
        }else{
            if((!empty($coupon_id) &&$tbpwd['coupon_id'] != $coupon_id) || empty($tbpwd['url'])){
                $tbpwd['tbpwd'] = $this->getTbkTpwd($goodinfo['title'],$goodinfo['pic_url'],$url,$pid,$num_iid,$coupon_id,'update');
            }
        }
        $goodinfo['jump_url'] = $url;
        if($request_from == 'hdk'){
            $goodinfo['pic_url'] = explode(',', $goodinfo['taobao_image']);
            unset($goodinfo['taobao_image']);
        }else{
            $goodinfo['pic_url'] = [$goodinfo['pic_url']];
        }
        
        $goodinfo['tbpwd'] = $tbpwd['tbpwd'];
        return $goodinfo;
    }
    
    
    //------------------------------------------2018-08-01--------------------------------------
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
}

