<?php

namespace app\tebiedemingzi\controller;

class Keyword extends Base
{
    public function _initialize() {
        parent::_initialize();
        $this->assign('lang', $GLOBALS['_LANG']);
    }
    
    public function index()
    {
        $actionobj = model('Keyword'); 
        $keywordlist = $actionobj->getKeywordList($this->pageSize);
        $this->assign('keywordlist',$keywordlist);
        $this->assign('page', $keywordlist->render());
        $this->assign('ur_here', '搜索词管理');
        $this->assign('action_link', array('text' => '添加搜索词', 'href' => '/tebiedemingzi/keyword/editKeyword'));
        return $this->display();
    }
    
    public function saveKeyword()
    {
        if(!\think\Validate::token('__token__','',$_POST)){
            return $this->url_redirect('非法数据来源', url("editAd"), "登录页");
        }
        $data['id'] = input('post.id');
        $data['keyword'] = input('post.keyword');
        $data['recommend'] = input('post.recommend');
        $data['status'] = input('post.status');
        $data['ordid'] = input('post.ordid');
        $result = model('Keyword')->saveData($data);
        return $this->url_redirect($result, "/tebiedemingzi/keyword/index", "搜索词列表");
        
    }
    
    public function editKeyword()
    {
        if($_POST){
            return $this->saveKeyword();exit;
        }
        if(input('id')){
            $keywordinfo = model('Keyword')->getById(input('id'));
        } else {
            $keywordinfo = [
                'id' => '',
                'keyword' => '',
                'recommend' => 0,
                'status' => 1,
                'ordid' => '',
            ];
            print_r($keywordinfo);
        }
        $this->assign('keywordinfo',$keywordinfo);
        $this->assign('ur_here', '搜索词编辑');
        return $this->display();
    }
    
    public function deleteKeyword()
    {
        $id = input('id');
        $result = model('keyword')->where('id',$id)->delete();
        if($result == 1){
            return $this->url_redirect('删除成功', "/tebiedemingzi/keyword/index", "搜索词列表");
        }else{
            return $this->url_redirect('删除失败', "/tebiedemingzi/keyword/index", "搜索词列表");
        }
        
    }
}

