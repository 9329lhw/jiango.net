<?php
namespace app\index\controller;
use think\Result;
use app\logic\AdLogic;

class Ad extends Base
{
      public function adInfo(){
        $id = input('id/d',1);
        $info = AdLogic::getAdInfo($id);
        $info['wechat_share'] = $this->wechatShare;
        Result::instance()->success('查询成功',$info)->output();
    }
}

