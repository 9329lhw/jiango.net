<?php
namespace app\index\controller;
use think\Result;
use app\logic\GoodsLogic;

class Search extends Base
{
    public function index()
    {
        $data = [
            'category' => get_category_by_type(2),
        ];
        $data['wechat_share'] = $this->wechatShare;
        Result::instance()->success('查询成功',$data)->output();
    }
    
    public function search()
    {
        $keyword = $this->request->param('keyword');
        $is_tmall = $this->request->param('is_tmall');
        $sort =  empty($this->request->param('sort')) ? 'coupon' : $this->request->param('sort');
        $page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
        $goods_list = GoodsLogic::instance()->getGoodsListByKeywordNew($keyword ,$page, $this->pageSize,$sort,$is_tmall); //商品列表
        $data = [
            'is_tmall' => $is_tmall,
            'sort' => $sort,
            'goods_list' => $goods_list,
            'wechat_share' => $this->wechatShare,
        ];
        $data['is_agent'] = 0;
        Result::instance()->success('查询成功',$data)->output();
        
    }
    
    public function getMore()
    {
        $keyword = $this->request->param('keyword');
        $is_tmall = $this->request->param('is_tmall');
        $sort =  empty($this->request->param('sort')) ? 'coupon' : $this->request->param('sort');
        $page = empty($this->request->param('page')) ? 2 : $this->request->param('page');
        $goods_list = GoodsLogic::instance()->getGoodsListByKeywordNew($keyword, $page, $this->pageSize,$sort,$is_tmall); //商品列表
        $data = [
            'is_tmall' => $is_tmall,
            'sort' => $sort,
            'goods_list' => $goods_list,
        ];
        Result::instance()->success('查询成功',$data)->output();
        
    }
    
    public function testSearch(){
        $this->pageSize = 100;
        $keyword = empty($this->request->param('keyword')) ? '鞋子' : $this->request->param('keyword');
        $page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
        $goods_list = GoodsLogic::instance()->getGoodsListByKeywordNew($keyword, $page, $this->pageSize); //商品列表
        Result::instance()->success('查询成功',$goods_list)->output();
    }
    
    
}
