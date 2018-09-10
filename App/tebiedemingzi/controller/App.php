<?php

namespace app\tebiedemingzi\controller;

class App extends Base
{
    public function index()
    {
        $actionobj = model('AppSeting'); 
        $adlist = $actionobj->getAppList();
        $this->assign('adlist',$adlist);
        $this->assign('ur_here', 'App版本管理');
        $this->assign('action_link', array('text' => '添加新版本', 'href' => '/tebiedemingzi/app/editApp'));
        return $this->display();
    }
    
    public function editApp()
    {
        if($_POST){
            return $this->saveApp();exit;
        }
        if(input('id')){
            $adinfo = model('AppSeting')->getById(input('id'));
        }else{
            $adinfo = [
                'id' => '',
                'app_code' => '',
                'desc' => '',
                'status' => '',
                'download_url' => '',
                'device' => '',
                'is_update' => '',
            ];
        }
        $this->assign('adinfo',$adinfo);
        $this->assign('ur_here', 'app版本编辑');
        return $this->display();
    }
    
    public function saveApp()
    {
        if(!\think\Validate::token('__token__','',$_POST)){
            return $this->url_redirect('非法数据来源', url("editAd"), "登录页");
        }
        $data['id'] = input('post.id');
        $data['app_code'] = input('post.app_code');
        $data['desc'] = input('post.desc');
        $data['status'] = input('post.status');
        $data['device'] = input('post.device');
        $data['is_update'] = input('post.is_update');
        $data['download_url'] = input('post.download_url');
        $result = model('AppSeting')->saveData($data);
        if($result){
            $this->up_cate_cache();
        }
        return $this->url_redirect($result, "/tebiedemingzi/app/index", "App版本管理");
        
    }
    
    public function up_cate_cache(){
        $applist = model('AppSeting')->where(['status'=>1])->select();
        $date = [];
        if(!empty($applist)){
            $applistarr = collection($applist)->toArray();
            foreach ($applistarr as $val){
                $date[$val['device']] = $val;
            }
            write_cache('app_seting',$date);
        }
    }
}

