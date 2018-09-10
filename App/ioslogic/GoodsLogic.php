<?php
namespace app\ioslogic;
use think\Base;
use think\Result;
use think\Request;
use think\Config;
use app\common\model\DtkGoods;
use app\common\model\TbGoods;
use app\common\model\GoodsTbPwd;
use app\common\model\TbCouponGoods;

class GoodsLogic extends Base {
    protected $key = 'jianggo';
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
    
    public function getGoodsList($page,$page_size) {
        $pre = ($page - 1) * $page_size;
        $list = DtkGoods::getList($pre,$page_size);
        foreach ($list as $key=>$val){
            $list[$key]['item_url'] = $val['item_url'].'&pid='.$this->pid;
            $list[$key]['coupon_price'] = ($val['coupon_price'] == 0) ? '' : $val['coupon_price'];
            $list[$key]['param'] = base64_encode('n='.$val['num_iid'].'&p='.$this->pid.'&f=dtk');
            $list[$key]['share_url'] = SERVER_PATH.'/goods?inviter='.$this->uid.'&param='.$list[$key]['param'];
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
    public function getGoodsListByCate($cate_id, $page, $page_size) {
        //关键字搜索
        if (empty($cate_id)) {
            Result::instance()->fail('分类id不能为空')->output();
        }
        $where = " and cate_id = $cate_id";
        $pre = ($page - 1) * $page_size;
//        $where = " and title like '%$cate_id%'";
        $tb_goods_list = DtkGoods::getList($pre, $page_size, $where);
        
        foreach ($tb_goods_list as $key => $val) {
            $tb_goods_list[$key]['item_url'] = $val['item_url'] . '&pid=' . $this->pid;
            $tb_goods_list[$key]['coupon_price'] = ($val['coupon_price'] == 0) ? '' : $val['coupon_price'];
            $tb_goods_list[$key]['param'] = base64_encode('n='.$val['num_iid'].'&p='.$this->pid.'&f=dtk');
            $tb_goods_list[$key]['share_url'] = SERVER_PATH.'/goods?inviter='.$this->uid.'&param=' . $tb_goods_list[$key]['param'];
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
    public function getGoodsListByKeywordNew($keyword, $page, $page_size) {
        //关键字搜索
        if (empty($keyword)) {
            Result::instance()->fail('关键字不能为空')->output();
        }
        
        //更新搜索词
        KeywordLogic::instance()->upKeyword($keyword);
        
        $tb_coupon_goods_list = $this->getItemCoupon($keyword);
        $num = count($tb_coupon_goods_list);
        $total_page = ceil($num / $page_size);
        $start = ($page - 1)*$page_size;
        if($page < $total_page){
            $tb_goods_list = array_slice($tb_coupon_goods_list,$start,$page_size);
            foreach ($tb_goods_list as $val) {
                $id = model('TbCouponGoods')->getGoodsByNumIid($val['num_iid'], $this->pid);
                if ($id) {
                    $data['id'] = $id;
                }
                $data['num_iid'] = $val['num_iid'];
                $data['pid'] = $this->pid;
                $data['title'] = $val['title'];
                $data['pic_url'] = $val['pic_url'];
                $data['price'] = $val['price'];
                $data['org_price'] = $val['org_price'];
                $data['coupon_price'] = $val['coupon_price'];
                $data['item_url'] = $val['item_url'];
                model('TbCouponGoods')->saveData($data);
            }
        }elseif($page == $total_page){
            $goods1 = array_slice($tb_coupon_goods_list,$start,$page_size);
            foreach ($goods1 as $val) {
                $id = model('TbCouponGoods')->getGoodsByNumIid($val['num_iid'], $this->pid);
                if ($id) {
                    $data['id'] = $id;
                }
                $data['num_iid'] = $val['num_iid'];
                $data['pid'] = $this->pid;
                $data['title'] = $val['title'];
                $data['pic_url'] = $val['pic_url'];
                $data['price'] = $val['price'];
                $data['org_price'] = $val['org_price'];
                $data['coupon_price'] = $val['coupon_price'];
                $data['item_url'] = $val['item_url'];
                model('TbCouponGoods')->saveData($data);
            }
            $goods1_num = count($goods1);
            if($goods1_num >= $page_size){
                $tb_goods_list = $goods1;
            }else{
                $diff_num = $page_size - $num;
                $page = 1;
                $goods2 = $this->getTbkItemNew($keyword, $page, $diff_num);
                if (!empty($goods2)) {
                    foreach ($goods2 as $val) {
                        $id = model('TbGoods')->getGoodsByNumIid($val['num_iid']);
                        if ($id) {
                            $data2['id'] = $id;
                        }
                        $data2['num_iid'] = $val['num_iid'];
                        $data2['title'] = $val['title'];
                        $data2['pic_url'] = $val['pic_url'];
                        $data2['price'] = $val['price'];
                        $data2['org_price'] = $val['org_price'];
                        model('TbGoods')->saveData($data2);
                    }
                }

                $tb_goods_list = array_merge($goods1, $goods2);
            }
        }else{
             $tb_goods_list = $this->getTbkItemNew($keyword, $page, $page_size);
             foreach ($tb_goods_list as $val) {
                $id = model('TbGoods')->getGoodsByNumIid($val['num_iid']);
                if ($id) {
                    $data['id'] = $id;
                }
                $data['num_iid'] = $val['num_iid'];
                $data['title'] = $val['title'];
                $data['pic_url'] = $val['pic_url'];
                $data['price'] = $val['price'];
                $data['org_price'] = $val['org_price'];
                model('TbGoods')->saveData($data);
            }
        }   
        
        return $tb_goods_list;
    }
    /**
     * 
     * @param type $keyword
     * @return type
     */
    public function getItemCoupon($keyword){
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
                $coupon_price = findNum($val['coupon_info']);
                $goods_list[$key]['item_url'] = $val['coupon_click_url'];
                $goods_list[$key]['num_iid'] = $val['num_iid'];
                $goods_list[$key]['title'] = $val['title'];
                $goods_list[$key]['price'] = $val['zk_final_price'] - $coupon_price;
                $goods_list[$key]['org_price'] = $val['zk_final_price'];
                $goods_list[$key]['pic_url'] = $val['pict_url'];
                $goods_list[$key]['coupon_price'] = $coupon_price;
                $goods_list[$key]['coupon_end_time'] = $val['coupon_end_time'];
                $goods_list[$key]['url_type'] = '';
                $goods_list[$key]['param'] = base64_encode('n='.$val['num_iid'].'&p='.$this->pid.'&f=coupon');
                $goods_list[$key]['share_url'] = SERVER_PATH . '/goods?inviter='.$this->uid.'&param=' . $goods_list[$key]['param'];
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
    public function getTbkItemNew($keyword, $page, $page_size){
        $c = new \TopClient;
        $c->appkey = $this->tbk_appkey;
        $c->secretKey = $this->tbk_secretKey;
        $req = new \TbkItemGetRequest;
        //num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick
        $req->setFields("num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick");
        $req->setQ("$keyword");
        $req->setPageNo("$page");
        $req->setPageSize("$page_size");
        $resp = $c->execute($req);
        $obj = object_to_array($resp->results);
        
        if(empty($obj['n_tbk_item'][0])){
            unset($obj['n_tbk_item']['small_images']);
            $tb_array = [$obj['n_tbk_item']];
        }else{
            $tb_array = $obj['n_tbk_item'];
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
                $goods_list[$key]['item_url'] = "https://uland.taobao.com/coupon/edetail?activityId=&itemId=".$val['num_iid'] . '&pid=' . $this->pid;
                $goods_list[$key]['num_iid'] = $val['num_iid'];
                $goods_list[$key]['title'] = $val['title'];
                $goods_list[$key]['price'] = $val['zk_final_price'];
                $goods_list[$key]['org_price'] = $val['reserve_price'];
                $goods_list[$key]['pic_url'] = $val['pict_url'];
                $goods_list[$key]['coupon_price'] = '';
                $goods_list[$key]['coupon_end_time'] = '';
                $goods_list[$key]['url_type'] = '';
                $goods_list[$key]['param'] = base64_encode('n='.$val['num_iid'].'&p='.$this->pid.'&f=global');
                $goods_list[$key]['share_url'] = SERVER_PATH . '/goods?inviter='.$this->uid.'&param=' . $goods_list[$key]['param'];
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
            'tbpwd' => $obj['model']
        ];
        
        if($action=='insert'){
           GoodsTbPwd::insert($tbpwd); 
        }else{
           GoodsTbPwd::where(['num_iid' => $num_iid,'pid' => $pid])->update(['coupon_id' => $coupon_id,'tbpwd' => $obj['model']]);
        }
        
        return $obj['model']; 
    }
    
    //获取商品详情
    public function getGoodsInfoNew($num_iid, $pid, $request_from) {
        if(empty($num_iid)){
            Result::instance()->fail('商品num_iid不能为空')->output();
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
        }else{
            $goodinfo = DtkGoods::getGoodsByNumId($num_iid);
            $request = 'dtk';
            $url ="https://uland.taobao.com/coupon/edetail?activityId=".$goodinfo['coupon_id']."&itemId=$num_iid"."&pid=$pid";
            $coupon_id = $goodinfo['coupon_id'];
        }
        //获取口令
        $tbpwd = GoodsTbPwd::getGoodsTbPwd($num_iid,$pid);
        if(empty($tbpwd)){
            $tbpwd['tbpwd'] = $this->getTbkTpwd($goodinfo['title'],$goodinfo['pic_url'],$url,$pid,$num_iid,$coupon_id,'insert');
        }else{
            if(!empty($coupon_id) &&($tbpwd['coupon_id'] != $coupon_id)){
                $tbpwd['tbpwd'] = $this->getTbkTpwd($goodinfo['title'],$goodinfo['pic_url'],$url,$pid,$num_iid,$coupon_id,'update');
            }
        }
        $goodinfo['pic_url'] = [$goodinfo['pic_url']];
        $goodinfo['tbpwd'] = $tbpwd['tbpwd'];
        return $goodinfo;
    }
}

