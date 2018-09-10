<?php
namespace app\admin\controller;

/**
 * 权限
 * @author jiang
 */
class Permission extends Base
{
    /**
     * 权限列表
     */
    public function index(){
        $actionobj = model('AdminAction'); 
//        $permissionlist = $actionobj->getActionTree();
        $permissionlist = $actionobj->getActionArr();
        $this->assign('permissionlist',$permissionlist);
        $this->assign('ur_here', '权限列表');
        $this->assign('action_link', array('text' => '添加模块权限', 'href' => 'editAction'));
        return $this->display();
    }
    
    /**
     * 添加/修改权限
     */
    public function editAction(){error_reporting(E_ERROR | E_PARSE);
        if($_POST){
            return $this->saveAction();exit;
        }
        if(input('id')){
            $action = model('AdminAction')->getByActionId(input('id'));
        }else{
            $action['parent_id'] = input('pid',0);
        }
        $this->assign('lang', $GLOBALS['_LANG']);
        $this->assign('action',$action);
        $this->assign('ur_here', '权限编辑');
        $this->assign('action_link', array('text' => '权限列表', 'href' => '/admin/Permission/index'));
        return $this->display();
    }
    
    /**
     * 保存权限
     */
    public function saveAction(){
        if(!\think\Validate::token('__token__','',$_POST)){
            return $this->url_redirect('非法数据来源', url("editAction"), "登录页");
        }
        $data['action_id'] = input('post.id');
        $data['parent_id'] = input('post.pid');
        $data['action_code'] = input('post.code');
        $data['action_name'] = input('post.name');
        $data['action_link'] = input('post.link');
        $data['is_show'] = input('post.show');
        $data['sort'] = input('post.sort');
        $result = model('AdminAction')->saveData($data);
        return $this->url_redirect($result, "/admin/permission/index", "权限列表");
    }
    
    /**
     * 异步检测权限标示
     */
    public function checkcode(){
        $code = input('code');
        $action = model('AdminAction')->getFieldByActionCode($code,'action_id');
        if($action && $action != input('id',0)){
            return json(false);
        }else{
            return json(true);
        }
    }
    
    /**
     * 删除权限
     */
    public function deleteAction(){
        $id = input('id','');
        if(empty($id)){
            $this->redirect('/admin/permission','',2);
        }else{
            $result = model('AdminAction')->deleteAction($id);
            if($result){
                return $this->url_redirect("删除成功", "/admin/permission", "权限列表");
            }else{
                return $this->url_redirect("删除失败", "/admin/permission", "权限列表");
            }
        }
    }
}

