<?php

namespace app\ios\controller;

use think\Result;
use app\ios\controller\Api;
use app\logic\CommontGoodsLogic;


class Special extends Api
{
    public function subjectHot(){
        $next_page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
        $subject = CommontGoodsLogic::instance()->subjectHot($next_page);
        $data = [
            'next_page' => $subject['page_code'],
            'next_page_url' => '/Special/subjectHot',
            'subject_list' => $subject['goods_list'],
        ];
        Result::instance()->success('查询成功', $data)->output();
    }
    
    public function selectedItem(){
        $next_page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
        $subject = CommontGoodsLogic::instance()->selectedItem($next_page);
        $data = [
            'next_page' => $subject['page_code'],
            'next_page_url' => '/Special/selectedItem',
            'selected_list' => $subject['goods_list'],
        ];
        Result::instance()->success('查询成功', $data)->output();
    }
}

