<?php

namespace app\api\controller;
use app\api\controller\Api;
use app\ioslogic\DrawsLogic;
use think\Result;

class Draws extends Api
{
    public function aplayInfo()
    {
        $uid = $this->request->param('uid');
        $info = DrawsLogic::instance()->getAplayInfo($uid);
        Result::instance()->success('请求成功',$info)->output();
    }
    
    public function check()
    {
        $uid = $this->request->param('uid');
        $tel = $this->request->param('tel');
        $real_name = $this->request->param('realname');
        $aplay_number = $this->request->param('aplay_number');
        DrawsLogic::instance()->checkDraws($uid,$tel,$real_name,$aplay_number);
        Result::instance()->success('添加成功')->output();
    }
    
    public function aplayDraws()
    {
        $uid = $this->request->param('uid');
        $amount = $this->request->param('amount');
        DrawsLogic::instance()->aplayDraws($uid,$amount);
        
        Result::instance()->success('请求成功')->output();
    }
    
    public function logsDraws(){
       $page = empty($this->request->param('page')) ? 1 : $this->request->param('page');
       $uid = $this->request->param('uid');
       $list = DrawsLogic::instance()->getDrawsList($uid,$page,$this->pageSize);
       Result::instance()->success('查询成功',$list)->output();
    }
}

