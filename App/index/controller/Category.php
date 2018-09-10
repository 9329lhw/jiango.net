<?php
namespace app\index\controller;
use think\Result;
use app\logic\GoodsLogic;
use app\logic\GoodsLogic;
class Category extends Base
{
    public function getList()
    {
        $sort =  $this->request->param('sort');
        $cat_name =  empty($this->request->param('tag')) ? '' : $this->request->param('tag');
        $page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
        $goods_list = GoodsLogic::instance()->getGoodsListByCate($cat_name ,$page, $this->pageSize,$sort); //商品列表
        $data = [
            'sort' => $sort,
            'goods_list' => $goods_list,
        ];
        $data['is_agent'] = $this->boss_agent;
        $data['wechat_share'] = $this->wechatShare;
        Result::instance()->success('查询成功',$data)->output();
    }

    public function getListMore()
    {
        $sort =  $this->request->param('sort');
        $cat_name =  empty($this->request->param('tag')) ? '' : $this->request->param('tag');
        $page = empty($this->request->param('page')) ? 2 : $this->request->param('page');
        $goods_list = GoodsLogic::instance()->getGoodsListByCate($cat_name, $page, $this->pageSize,$sort); //商品列表
        $data = [
            'sort' => $sort,
            'goods_list' => $goods_list,
        ];
        Result::instance()->success('查询成功',$data)->output();
    }
    
    /**
     * 好单库商品列表
     */
    public function getGoodsList()
    {
        $type =  $this->request->param('tag');
        $sort =  empty($this->request->param('sort')) ? 0 : $this->request->param('sort');
        $next_page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
        $goods_info = GoodsLogic::instance()->getGoodsListByHdk($type, 0, $this->newPageSize, $next_page, $sort); //商品列表
        $data = [
            'tag' => $type,
            'sort' => $sort,
            'next_page' => $goods_info['page_code'],
            
            'goods_list' => $goods_info['goods_list'],
        ];
        $data['is_agent'] = $this->boss_agent;
        $data['wechat_share'] = $this->wechatShare;
        Result::instance()->success('查询成功',$data)->output();
    }
    
    /**
     * 好单库商品分类
     */
    public function getColumnGoodsList()
    {
        $type =  $this->request->param('tag');
        $sort =  empty($this->request->param('sort')) ? 0 : $this->request->param('sort');
        $next_page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
        $goods_info = GoodsLogic::instance()->getGoodsListByHdk($type , $this->newPageSize, $next_page, $sort); //商品列表
        $data = [
            'tag' => $type,
            'sort' => $sort,
            'next_page' => $goods_info['page_code'],
            'next_page_url' => '/category/getColumnGoodsList',
            'goods_list' => $goods_info['goods_list'],
        ];
        $data['is_agent'] = $this->boss_agent;
        $data['wechat_share'] = $this->wechatShare;
        Result::instance()->success('查询成功',$data)->output();
    }
    
    public function getCateList(){
          //$tag 0全部，1女装，2男装，3内衣，4美妆，5配饰，6鞋品，7箱包，8儿童，9母婴，10居家，11美食，12数码，13家电，14其他，15车品，16文体
        $tag =  $this->request->param('tag');
        //$sort 0.综合（最新），1.券后价(低到高)，2.券后价（高到低），3.券面额（高到低），4.月销量（高到低），5.佣金比例（高到低），6.券面额（低到高），7.月销量（低到高），8.佣金比例（低到高），9.全天销量（高到低），10全天销量（低到高），11.近2小时销量（高到低），12.近2小时销量（低到高）注意：该排序仅对nav=3，4，5有效，1，2无效
        $sort =  $this->request->param('sort');
        //$nav 商品筛选类型：nav=1是今日上新（当天新券商品），nav=2是9.9包邮，nav=3是30元封顶，nav=4是聚划算，nav=5是淘抢购，nav=6是0点过夜单，nav=7是预告单，nav=8是品牌单，nav=9是天猫商品，nav=10是视频单
        $nav =  $this->request->param('nav');
        //$type 跳转：type=1分类列表跳转，type=2爆款列表跳转，type=3h5跳转，type=4是分享跳转，type=5是商品详情，type=6淘宝跳转，type=7抽奖，type=8邀请赚
        $type =  $this->request->param('type');
        //当前页
        $next_page = $this->request->param('page');
        
        if($type == 1){
            $goods_info = GoodsLogic::instance()->getGoodsListByHdk(3, $tag, $this->newPageSize, $next_page, $sort); 
        }elseif($type == 2){
            $goods_info = GoodsLogic::instance()->getGoodsListByHdk(2, 0, $this->newPageSize, $next_page, $sort);
        }else{
            $goods_info = GoodsLogic::getGoodsListByColumn($nav, $this->newPageSize, $next_page, $sort);
        }
        $data = [
            'tag' => $tag,
            'sort' => $nav,
            'nav' => $sort,
            'type' => $type,
            'next_page' => $goods_info['page_code'],
            'next_page_url' => '/category/getCateList',
            'goods_list' => $goods_info['goods_list'],
        ];
        $data['is_agent'] = $this->boss_agent;
        $data['wechat_share'] = $this->wechatShare;
        Result::instance()->success('查询成功',$data)->output();
    }
    
}
