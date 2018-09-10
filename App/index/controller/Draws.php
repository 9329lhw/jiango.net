<?php

namespace app\index\controller;
use app\logic\DrawsLogic;
use app\logic\GoodsLogic;
use think\Result;

class Draws extends Base
{
    
    public function aplayInfo()
    {
        $uid = $this->uid;
        $info = DrawsLogic::instance()->getAplayInfo($uid);
        $info['wechat_share'] = $this->wechatShare;
        $info['goods_list'] = GoodsLogic::instance()->getTop100();
        Result::instance()->success('请求成功',$info)->output();
    }
    
    public function check()
    {
        $uid = $this->uid;
        $tel = $this->request->param('tel');
        $real_name = $this->request->param('realname');
        $aplay_number = $this->request->param('aplay_number');
        DrawsLogic::instance()->checkDraws($uid,$tel,$real_name,$aplay_number);
        Result::instance()->success('添加成功')->output();
    }
    
    public function aplayDraws()
    {
        $uid = $this->uid;
        $amount = $this->request->param('amount');
        DrawsLogic::instance()->aplayDraws($uid,$amount);
        
        Result::instance()->success('请求成功')->output();
    }
    
    public function logsDraws(){
       $uid = $this->uid;
       $page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
       $data = DrawsLogic::instance()->getDrawsList($uid,$page,$this->pageSize);
       Result::instance()->success('查询成功',$data)->output();
    }
}

