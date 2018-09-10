<?php
namespace app\ios\controller;
use think\Result;
use app\logic\CommontGoodsLogic;

class Category extends Api
{
    public function getList()
    {
        $cat_name =  empty($this->request->param('tag')) ? '' : $this->request->param('tag');
        $page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
        $sort =  $this->request->param('sort');
        $goods_list = CommontGoodsLogic::instance()->getGoodsListByCate($cat_name ,$page, $this->pageSize,$sort); //商品列表
        $data = [
            'sort' => $sort,
            'goods_list' => $goods_list,
        ];
        Result::instance()->success('查询成功',$data)->output();
    }

    //搜索加载更多
    public function getListMore()
    {
        $cat_name =  $this->request->param('tag');
        $page = empty($this->request->param('page')) ? 2 : $this->request->param('page');
        $coupon_rate =  $this->request->param('coupon_rate');
        $update_time =  $this->request->param('update_time');
        $goods_list = CommontGoodsLogic::instance()->getGoodsListByCate($cat_name, $page, $this->pageSize,$coupon_rate,$update_time); //商品列表
        $data = [
            'coupon_rate' => $coupon_rate,
            'update_time' => $update_time,
            'goods_list' => $goods_list,
        ];
        Result::instance()->success('查询成功',$data)->output();
    }
    
    /**
     * 好单库商品列表
     */
    public function getGoodsList()
    {
        $sort =  empty($this->request->param('sort')) ? 0 : $this->request->param('sort');
        $type =  $this->request->param('tag');
        $next_page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
        $goods_info = CommontGoodsLogic::instance()->getGoodsListByHdk($type , 0, $this->pageSize, $next_page,$sort); //商品列表
        $data = [
            'tag' => $type,
            'next_page' => $goods_info['page_code'],
            'next_page_url' => '/category/getGoodsList',
            'sort' => $sort,
            'goods_list' => $goods_info['goods_list'],
        ];
        Result::instance()->success('查询成功',$data)->output();
    }
    
    /**
     * 好单库商品分类
     */
    public function getColumnGoodsList()
    {
        $sort =  $this->request->param('sort');
        $type =  $this->request->param('tag');
        $next_page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
        $goods_info = CommontGoodsLogic::instance()->getGoodsListByColumn($type ,$next_page, $this->pageSize,$sort); //商品列表
        $data = [
            'tag' => $type,
            'next_page' => $goods_info['page_code'],
            'next_page_url' => '/category/getColumnGoodsList',
            'sort' => $sort,
            'goods_list' => $goods_info['goods_list'],
        ];
        Result::instance()->success('查询成功',$data)->output();
    }
    
    
    /**
     * 今日爆款
     */
    public function getHotGoodsList(){
        $next_page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
        $goods_info = CommontGoodsLogic::instance()->getHomeGoodsList($next_page); //商品列表
        $data = [
            'next_page' => $goods_info['page_code'],
            'next_page_url' => '/index/getmore',
            'goods_list' => $goods_info['goods_list'],
        ];
        Result::instance()->success('查询成功',$data)->output();
    }
    
    /**
     * 猜你喜欢
     */
    
    public function getSimilarGoodsList(){
        $next_page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
        $itemid = $this->request->param('itemid');
        $goods_info = CommontGoodsLogic::instance()->getSimilarInfo($itemid,$next_page); //商品列表
        $data = [
            'next_page' => $goods_info['page_code'],
            'next_page_url' => '/index/getmore',
            'goods_list' => $goods_info['goods_list'],
        ];
        Result::instance()->success('查询成功',$data)->output();
    }
    
    public function getCateList(){
        //$tag 0全部，1女装，2男装，3内衣，4美妆，5配饰，6鞋品，7箱包，8儿童，9母婴，10居家，11美食，12数码，13家电，14其他，15车品，16文体
        $tag =  $this->request->param('tag');
        //$sort 0.综合（最新），1.券后价(低到高)，2.券后价（高到低），3.券面额（高到低），4.月销量（高到低），5.佣金比例（高到低），6.券面额（低到高），7.月销量（低到高），8.佣金比例（低到高），9.全天销量（高到低），10全天销量（低到高），11.近2小时销量（高到低），12.近2小时销量（低到高）注意：该排序仅对nav=3，4，5有效，1，2无效
        $sort =  empty($this->request->param('sort')) ? 0 : $this->request->param('sort');
        //$nav 商品筛选类型：nav=1是今日上新（当天新券商品），nav=2是9.9包邮，nav=3是30元封顶，nav=4是聚划算，nav=5是淘抢购，nav=6是0点过夜单，nav=7是预告单，nav=8是品牌单，nav=9是天猫商品，nav=10是视频单
        $nav =  empty($this->request->param('nav')) ? 1 : $this->request->param('nav');
        //$type 跳转：type=1分类列表跳转，type=2爆款列表跳转，type=3h5跳转，type=4是分享跳转，type=5是商品详情，type=6淘宝跳转
        $type =  $this->request->param('type');
        //当前页
        $next_page = empty($this->request->param('page')) ? 1 : $this->request->param('page');

        if($type == 1){
            $goods_info = CommontGoodsLogic::instance()->getGoodsListByHdk(3, $tag, $this->pageSize, $next_page, $sort); 
        }elseif($type == 2){
            $goods_info = CommontGoodsLogic::instance()->getGoodsListByHdk(2, 0, $this->pageSize, $next_page, $sort);
        }else{
            $goods_info = CommontGoodsLogic::instance()->getGoodsListByColumn($nav, $next_page, $this->pageSize, $sort);
        }
        $data = [
            'tag' => $tag,
            'sort' => $sort,
            'nav' => $nav,
            'type' => $type,
            'next_page' => $goods_info['page_code'],
            'next_page_url' => '/category/getCateList',
            'goods_list' => $goods_info['goods_list'],
        ];
        Result::instance()->success('查询成功',$data)->output();
    }
}
