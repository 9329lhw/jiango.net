<?php

namespace app\admin\controller;

class Ad extends Base
{
    public function _initialize() {
        parent::_initialize();
        $this->assign('lang', $GLOBALS['_LANG']);
    }
    
    public function index()
    {
        $actionobj = model('Ad'); 
        $adlist = $actionobj->getAdList($this->pageSize);
        $this->assign('adlist',$adlist);
        $this->assign('page', $adlist->render());
        $this->assign('ur_here', '广告管理');
        $this->assign('action_link', array('text' => '添加广告', 'href' => '/admin/ad/editAd'));
        return $this->display();
    }
    
    public function saveAd()
    {
        if(!\think\Validate::token('__token__','',$_POST)){
            return $this->url_redirect('非法数据来源', url("editAd"), "登录页");
        }
        if(!empty(request()->file(extimg))){
            $img_url = upload('extimg',1); //上传图片
            $data['extimg'] = $img_url;
        }
        $data['id'] = input('post.id');
        $data['board_id'] = input('post.board_id');
        $data['name'] = input('post.name');
        $data['url'] = input('post.url');
        $data['status'] = input('post.status');
        $data['ordid'] = input('post.ordid');
        $data['tbpwd'] = input('post.tbpwd');
        $result = model('Ad')->saveData($data);
        return $this->url_redirect($result, "/admin/ad/index", "广告列表");
        
    }
    
    public function editAd()
    {
        if($_POST){
            return $this->saveAd();exit;
        }
        if(input('id')){
            $adinfo = model('Ad')->getById(input('id'));
        } else {
            $adinfo = [
                'id' => '',
                'board_id' => '',
                'name' => '',
                'url' => '',
                'ordid' => '',
                'tppwd' => '',
            ];
        }
        $this->assign('adinfo',$adinfo);
        $this->assign('ur_here', '广告编辑');
        return $this->display();
    }
    
    public function deleteAd()
    {
        $id = input('id');
        $result = model('Ad')->where('id',$id)->delete();
        if($result == 1){
            return $this->url_redirect('删除成功', "/admin/category/index", "广告列表");
        }else{
            return $this->url_redirect('删除失败', "/admin/category/index", "广告列表");
        }
        
    }
}

