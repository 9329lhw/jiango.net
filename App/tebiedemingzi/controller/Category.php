<?php

namespace app\tebiedemingzi\controller;

class Category extends Base
{
    protected $cate_position = [
        '0' => '请选择位置',
        '1' => '首页',
        '2' => '搜索页',
        '3' => '专题页',
        '4' => '新版app首页',
        '5' => '新版web首页'
    ];
    
    protected $cate_type = [
        '0' => '请选择跳转位置',
        '1' => '分类列表跳转',
        '2' => '爆款列表跳转',
        '3' => 'h5跳转',
        '4' => '分享跳转',
        '5' => '商品详情',
        '6' => '淘宝跳转'
    ];
    
    public function _initialize() {
        parent::_initialize();
        $this->assign('lang', $GLOBALS['_LANG']);
    }
    
    public function index()
    {
        $actionobj = model('Category'); 
        $catelist = $actionobj->where(['status'=>1])->order('ordid desc')->paginate($this->pageSize);
        foreach ($catelist as $key=>$val){
            $catelist[$key]['board_id'] = $this->cate_position[$val['board_id']];
            $catelist[$key]['type'] = $this->cate_type[$val['type']];
        }
        $this->assign('catelist',$catelist);
        $this->assign('page', $catelist->render());
        $this->assign('ur_here', '分类管理');
        $this->assign('action_link', array('text' => '添加分类', 'href' => '/tebiedemingzi/category/editCategory'));
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
        $data['type'] = input('post.type');
        $data['param'] = input('post.param');
        $data['tag'] = input('post.tag');
        $data['desc'] = input('post.desc');
        $result = model('Category')->saveData($data);
        if($result){
            $this->up_cate_cache();
        }
        return $this->url_redirect($result, "/tebiedemingzi/category/index", "分类列表");  
        
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
                'type'=>'',
                'param'=>'',
                'ordid'=>'',
                'tag'=>'',
                'extimg'=>'',
                'desc'=>'',
                'status'=>'',
                ];
        }
        $this->assign('cate_type',$this->cate_type);
        $this->assign('cate_position',$this->cate_position);
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
            return $this->url_redirect('删除成功', "/tebiedemingzi/category/index", "分类列表");
        }else{
            return $this->url_redirect('删除失败', "/tebiedemingzi/category/index", "分类列表");
        }
        
    }
    
    public function up_cate_cache(){
        $catelist = model('Category')->field('id,name,board_id,url,tag,type,nav,param,extimg,desc')->where(['status'=>1])->order('ordid desc')->select();
        if(!empty($catelist)){
            $catelist = collection($catelist)->toArray();
            write_cache('category',$catelist);
        }
    }
    
}

