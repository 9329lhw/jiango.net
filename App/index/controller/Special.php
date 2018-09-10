<?php

namespace app\index\controller;
use think\Result;
use app\logic\CommontGoodsLogic;


class Special extends Base
{
    public function subjectHot(){
        $next_page = empty($this->request->param('next_page')) ? 1 : $this->request->param('next_page');
        $subject = CommontGoodsLogic::instance()->subjectHot($next_page);
        $data = [
            'next_page' => $subject['page_code'],
            'next_page_url' => '/Special/subjectHot',
            'goods_list' => $subject['goods_list'],
        ];
        Result::instance()->success('查询成功', $data)->output();
    }
    
    public function selectedItem(){
        $next_page = empty($this->request->param('next_page')) ? 1 : $this->request->param('next_page');
        $subject = CommontGoodsLogic::instance()->selectedItem($next_page);
        $data = [
            'next_page' => $subject['page_code'],
            'next_page_url' => '/Special/selectedItem',
            'goods_list' => $subject['goods_list'],
        ];
        Result::instance()->success('查询成功', $data)->output();
    }
}

