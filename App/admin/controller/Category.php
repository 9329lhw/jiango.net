<?php

namespace app\admin\controller;

class Category extends Base
{
    public function _initialize() {
        parent::_initialize();
        $this->assign('lang', $GLOBALS['_LANG']);
    }
    
    public function index()
    {
        $actionobj = model('Category'); 
        $catelist = $actionobj->getCateList();
        $this->assign('catelist',$catelist);
        $this->assign('ur_here', '分类管理');
        $this->assign('action_link', array('text' => '添加分类', 'href' => '/admin/category/editCategory'));
        return $this->display();
    }
    
    public function saveCategory()
    {
        if(!\think\Validate::token('__token__','',$_POST)){
            return $this->url_redirect('非法数据来源', url("editAd"), "登录页");
        }
        if(!empty(request()->file(extimg))){
            $img_url = upload('extimg',3); //上传图片
            $data['extimg'] = $img_url;
        }
        $data['id'] = input('post.id');
        $data['name'] = input('post.name');
        $data['board_id'] = input('post.board_id');
        $data['url'] = input('post.url');
        $data['status'] = input('post.status');
        $data['ordid'] = input('post.ordid');
        $data['tag'] = input('post.tag');
        $data['desc'] = input('post.desc');
        $result = model('Category')->saveData($data);
        if($result){
            $this->up_cate_cache();
        }
        return $this->url_redirect($result, "/admin/category/index", "分类列表");  
        
    }
    
    public function editCategory()
    {
        if($_POST){
            return $this->saveCategory();exit;
        }
        if(input('id')){
            $cateinfo = model('Category')->getById(input('id'));
        }else{
            $cateinfo = [
                'id'=>'',
                'name'=>'',
                'board_id'=>'',
                'url'=>'',
                'ordid'=>'',
                'tag'=>'',
                'extimg'=>'',
                'desc'=>'',
                'status'=>'',
                ];
        }
        
        $this->assign('cateinfo',$cateinfo);
        $this->assign('ur_here', '分类编辑');
        return $this->display();
    }
    
    public function deleteCategory()
    {
        $id = input('id');
        $result = model('Category')->where('id',$id)->delete();
        if($result == 1){
            $this->up_cate_cache();
            return $this->url_redirect('删除成功', "/admin/category/index", "分类列表");
        }else{
            return $this->url_redirect('删除失败', "/admin/category/index", "分类列表");
        }
        
    }
    
    public function up_cate_cache(){
        $catelist = model('Category')->field('id,name,board_id,url,tag,extimg,desc')->where(['status'=>1])->order('ordid desc')->select();
        if(!empty($catelist)){
            $catelist = collection($catelist)->toArray();
            write_cache('category',$catelist);
        }
    }
    
}

