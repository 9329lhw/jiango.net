<?php

namespace app\admin\model;

class AdminAction extends Base{
    
    /**
     * 获取权限树
     */
    public function getActionTree($id = 0) {
        $action = $this->getByActiontId($id);
        if ($action || $id == 0) {
            $actionArr = $this->where(array('parent_id' => $id))->order('sort')->select();
            foreach ($actionArr as $key => $arr) {
                $actionArr[$key]['child'] = $this->getActionTree($arr['action_id']);
            }
        }
        return $actionArr;
    }

    /**
     * 获取菜单树
     */
    public function getMenuTree($id = 0, $type = '') {
        $menuArr = array();
        if (session('action_list') == 'all') {
            $where['parent_id'] = 0;
            $where['is_show'] = 1;
            $menulist = $this->where($where)->order('sort asc')->select();
            foreach ($menulist as $key => $arr) {
                $menuArr[$arr['action_id']] = $arr;
                $where['parent_id'] = $arr['action_id'];
                $menuArr[$arr['action_id']]['child'] = $this->where($where)->order('sort asc')->select();
            }
        }else{
            $where['a.is_show'] = 1;
            $where['a.action_code'] = array('in',session('action_list'));
            $menulist = $this->alias('a')->field('a.*')->where($where)->join('hhs_admin_action as b on b.action_id=a.parent_id')->order('a.sort')->select();
            foreach ($menulist as $key => $arr) {
                if(!isset($menuArr[$arr['parent_id']])){
                    $menuArr[$arr['parent_id']] = $this->getByActionId($arr['parent_id']);
                }
                $menuArr[$arr['parent_id']]['child'][] = $arr;
            }
        }
        return $menuArr;
    }

    /**
     * 获取分类数组,子类合并为同级数组
     */
    public function getActionArr($id = 0, $map = '', $level = 0, $childflag = true) {
        $where['parent_id'] = $id;
        if (is_array($map)) {
            $where = array_merge($where, $map);
        } else {
            if ($map != '') {
                $where['_string'] = $map;
            }
        }
        $menu = $this->getByActionId($id);
        $menuArr = array();
        if ($menu || $id == 0) {
            $menuArr = $this->where($where)->order('sort')->select();
        }
        $menulist = array();
        foreach ($menuArr as $key => $val) {
            $menuArr[$key]['level'] = $level;
            $menulist[] = $menuArr[$key];
            //遍历获取子类
            if ($childflag) {
                $menulist = array_merge($menulist, $this->getActionArr($val['action_id'], $map, $level + 1));
            }
        }
        return $menulist;
    }

    /**
     * 删除
     */
    public function deleteAction($id) {
        $result = $this->where('action_id = ' . $id . ' or parent_id = ' . $id)->delete();
        return $result;
    }
}
