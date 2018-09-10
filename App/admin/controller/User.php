<?php

namespace app\admin\controller;

/**
 * 会员
 * @author jiang
 */
class User extends Base {
    
    public function index(){
        if (input('request.act') != '') {
            return $this->ajaxEdit();
        }
        $where = array();
        $nickname = input('nickname', '');
        if($nickname){
            $where['nickname'] = ['like',"%{$nickname}%"];
        }
        if(input('first_agent/d', 0)){
            $where['first_agent_num'] = ['gt',0];
        }
        if(input('second_agent/d', 0)){
            $where['second_agent_num'] = ['gt',0];
        }
        if(input('boss_agent/d', 0)){
            $where['boss_agent'] = 1;
        }
        $start_create_time = input('start_create_time', '');
        $end_create_time = input('end_create_time', '');
        if(!empty($start_create_time) && !empty($end_create_time)){
            $where['create_time'] = ['between',[strtotime($start_create_time),strtotime($end_create_time.'+1 days')]];
        }else{
            if(!empty($start_create_time)){
                $where['create_time'] = ['gt',strtotime($start_create_time)];
            }
            if(!empty($end_create_time)){
                $where['create_time'] = ['lt',strtotime($end_create_time.'+1 days')];
            }
        }
        $sort_by = input('sort_by','create_time');
        $sort_order = input('sort_order','desc');
        $this->assign('sort_by',$sort_by);
        $this->assign('sort_order',$sort_order);
        $sort_flag = sortFlag($sort_by, $sort_order);
        $this->assign($sort_flag['tag'], $sort_flag['img']);
        $list = model('User')->where($where)->order("$sort_by $sort_order")->paginate($this->pageSize);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        
        return $this->display();
    }
    
    /**
     * 列表上修改
     */
    public function ajaxEdit() {
        $editArr = array();
        $id = input('post.id/d');
        $userObject = model('User');
        if ($id <= 0) {
            return json($userObject->ajaxError(1));
        } else {
            $editArr['uid'] = $content = $id;
        }
        $act = substr(input('post.act'), 5);
        if ($act !== '') {
            $editArr[$act] = $content = trim(input('post.val'));
        } else {
            return json($userObject->ajaxError(2));
        }
        if (!empty($editArr)) {
            $user = $userObject->find($id);
            if(!$user){
                return json($userObject->ajaxError(4));
            }
            $user->$act = $content;
            if($act == 'boss_agent' && $content){
                $user->boss_agent_time = time();
            }
            $result = $user->save();
            if($result !== false){
                if($act == 'boss_agent' && $content){
                    model('User')->belongBoss($id,$id);
                }
                model('AdminLog')->adminLogAdd($user->uid.'-'.$user->nickname.",$act-$content",'edit','user');
            }else{
                return json($userObject->ajaxError(100,'','设置失败'));
            }
            return json($userObject->ajaxError(0, $content));
        } else {
            return json($userObject->ajaxError(2));
        }
    }
    
}

