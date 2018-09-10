<?php
namespace app\ios\controller;
use think\Result;
use app\ioslogic\ApiGoodsLogic;

class Search extends Api
{
    public function index()
    {
        $data = [
            'category' => get_category_by_type(2),
        ];
        Result::instance()->success('查询成功',$data)->output();
    }
    
    public function search()
    {
        $keyword = $this->request->param('keyword');
        $is_tmall = $this->request->param('is_tmall');
        $sort =  empty($this->request->param('sort')) ? 'coupon' : $this->request->param('sort');
        $page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
        $goods_list = ApiGoodsLogic::instance()->getGoodsListByKeywordNew($keyword ,$page, $this->pageSize,$sort,$is_tmall); //商品列表
        $data = [
            'is_tmall' => $is_tmall,
            'sort' => $sort,
            'goods_list' => $goods_list,
        ];
        Result::instance()->success('查询成功',$data)->output();
    }

    //搜索加载更多
    public function getMore()
    {
        $keyword = $this->request->param('keyword');
        $coupon_rate =  $this->request->param('coupon_rate');
        $update_time =  $this->request->param('update_time');
        $page = empty($this->request->param('page')) ? 2 : $this->request->param('page');
        $goods_list = ApiGoodsLogic::instance()->getGoodsListByKeywordNew($keyword, $page, $this->pageSize,$coupon_rate,$update_time); //商品列表
        $data = [
            'coupon_rate' => $coupon_rate,
            'update_time' => $update_time,
            'goods_list' => $goods_list,
        ];
        Result::instance()->success('查询成功',$data)->output();
    }
    
    public function testSearch(){
        $this->pageSize = 100;
        $keyword = empty($this->request->param('keyword')) ? '' : $this->request->param('keyword');
        $page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
        $goods_list = ApiGoodsLogic::instance()->getGoodsListByKeywordNew($keyword, $page, $this->pageSize); //商品列表
        $data = [
            'goods_list' => $goods_list,
        ];
        Result::instance()->success('查询成功',$data)->output();
    }
}
