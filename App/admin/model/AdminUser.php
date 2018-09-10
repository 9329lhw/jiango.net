<?php

namespace app\admin\model;

/**
 * 后台管理员
 */
class AdminUser extends Base{
    
    
    /**
     * 检查登录
     */
    public function checkLogin($username, $passwd) {
        $user = $this->getByUserName($username);
        if ($user) {
            if (md5(md5($passwd) . $user['ec_salt']) == $user['password']) {
                return $user;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
    
    /**
     * 修改个人信息
     */
    public function saveUserInfo($data) {
        $userInfo = $this->getByUserId($data['user_id'])->toArray();
        if ($data['new_password'] != '') {
            if ($userInfo['password'] != md5(md5($data['old_password']) . $userInfo['ec_salt'])) {
                return '密码错误';
            }
            $userInfo['password'] = md5(md5($data['new_password']) . $userInfo['ec_salt']);
            $userInfo['password_confirm'] = md5(md5($data['pwd_confirm']) . $userInfo['ec_salt']);
        }
        foreach($data as $k=>$v){
            if(isset($userInfo[$k])){
                $userInfo[$k] = $data[$k];
            }
        }
//        $data = ['user_name'=>'admin','user_id'=>1,'email'=>'875341655@qq.com','password'=>'8e8aa6a5f7fa57948c94430fabf534cb'];
        $result = $this->validate('AdminUser.edit')->isUpdate(true)->allowField(true)->save($userInfo);
        if ($result === false) {
            return $this->getError();
        }
        return "修改成功";
    }
    
    /**
     * 获取管理员列表
     */
    public function getAdminList() {
        $list = $this->field('user_id, user_name, email, add_time, last_login')->order('user_id desc')->select();
        foreach ($list as $k => $v) {
            $list[$k]['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
            if ($v['last_login'] > 0)
                $list[$k]['last_login'] = date('Y-m-d H:i:s', $v['last_login']);
            else
                $list[$k]['last_login'] = 'N/A';
        }
        return $list;
    }
    
    /**
     * 添加管理员
     */
    public function insertAdminUser(&$ctrObj) {
        $roleObj = D('Role');
        $action_list = $roleObj->where('role_id=' . I('post.select_role/d'))->getField('action_list');

        $nav_list = $this->where('action_list = \'all\'')->getField('nav_list');
        $ec_salt = rand(1000, 9999);
        $data = array(
            'user_name' => I('post.user_name', 'trim'),
            'email' => I('post.email', 'trim'),
            'ec_salt' => $ec_salt,
            'password' => md5(md5(I('post.password')) . $ec_salt),
            'pwd_confirm' => md5(md5(I('post.pwd_confirm')) . $ec_salt),
            'add_time' => time(),
            'nav_list' => $nav_list,
            'action_list' => $action_list,
            'role_id' => I('post.select_role/d'),
            '__hash__' => I('post.__hash__')
        );
        if ($this->create($data, 1)) {
            if ($newId = $this->add()) {
                $link[0]['text'] = $ctrObj->_LANG['go_allot_priv'];
                $link[0]['url'] = '/Admin/Admin/adminUserAllot/id/' . $newId . '/user/' . I('post.user_name', 'trim');

                $link[1]['text'] = $ctrObj->_LANG['continue_add'];
                $link[1]['url'] = '/Admin/Admin/adminUserAdd';
                $ctrObj->url_redirect($ctrObj->_LANG['_LANG']['add'] . "&nbsp;" . I('post.user_name', 'trim') . "&nbsp;" . $ctrObj->_LANG['action_succeed'], $link);
            } else {
                $ctrObj->url_redirect('添加失败！', '', '添加管理员');
            }
        } else {
            $ctrObj->url_redirect('添加失败！' . $this->getError(), '', '添加管理员');
        }
    }

    /**
     * 编辑管理员
     */
    public function editAdminUser(&$ctrObj) {
        $id = I('post.id/d');
        $data = array();
        $newPswd = I('post.new_password');
        if ($id <= 0)
            $ctrObj->url_redirect('非法操作！', '', '编辑管理员');
        $userInfo = $this->getByUserId($data['user_id']);
        if (!empty($newPswd)) {
            if ($userInfo['password'] == md5(md5(I('post.old_password')) . $userInfo['ec_salt'])) {
                $data['password'] = md5(md5($newPswd) . $userInfo['ec_salt']);
                $data['pwd_confirm'] = md5(md5(I('post.pwd_confirm')) . $userInfo['ec_salt']);
            } else {
                $ctrObj->url_redirect('旧密码错误！', '', '编辑管理员');
            }
        }
        $role_id = I('post.select_role/d');
        if ($role_id > 0 && $role_id != $userInfo['role_id']) {
            $data['role_id'] = $role_id;

            $roleObj = D('Role');
            $data['action_list'] = $roleObj->where(array('role_id' => $role_id))->getField('action_list');
        }
        $data['user_id'] = $id;
        $data['user_name'] = I('post.user_name');
        $data['email'] = I('post.email');
        $data['__hash__'] = I('post.__hash__');
        if ($this->create($data, 2)) {
            if ($this->save() !== FALSE) {
                $ctrObj->url_redirect('编辑成功！', '/Admin/Admin/adminlist', '管理员列表');
            } else {
                $ctrObj->url_redirect('编辑失败！' . $this->getError(), '', '编辑管理员');
            }
        } else {
            $ctrObj->url_redirect('编辑失败！' . $this->getError(), '', '编辑管理员');
        }
    }
}
