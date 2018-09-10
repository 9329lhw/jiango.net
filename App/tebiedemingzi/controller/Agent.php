<?php

namespace app\tebiedemingzi\controller;

use app\common\model\User;

/**
 * 代理
 * @author jiang
 */
class Agent extends Base {

    public function index() {
        if (input('request.act') != '') {
            return $this->ajaxEdit();
        }
        $type = input('type', 'first_agent');
        $where = array();
        if ($type == 'first_agent') {
            $where['first_agent_num'] = ['gt', 0];
        } elseif ($type == 'second_agent') {
            $where['second_agent_num'] = ['gt', 0];
        } elseif ($type == 'boss_agent') {
            $where['boss_agent'] = 1;
        } elseif ($type == 'partner_agent') {
            $where['partner_agent'] = 1;
        }
        $nickname = input('nickname', '');
        if ($nickname) {
            $where['nickname'] = ['like', "%{$nickname}%"];
        }
        $list = model('User')->field('uid,nickname,headimgurl,boss_agent,pid,partner_agent')->where($where)->paginate($this->pageSize);
        $this->assign('list', $list);
        $this->assign('type_list', ['first_agent' => '一级', 'second_agent' => '二级', 'boss_agent' => '代言人', 'partner_agent' => '合伙人']);
        $this->assign('page', $list->render());
        $this->assign('ur_here', '代理列表');
        $this->assign('action_link', array('text' => '分佣设置', 'href' => 'commission'));
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
            if (!$user) {
                return json($userObject->ajaxError(4));
            }
            $user->$act = $content;
            if ($act == 'boss_agent' && $content) {
                $user->boss_agent_time = time();
            }
            if ($act == 'partner_agent' && $content) {
                $user->partner_agent_time = time();
            }
            if ($act == 'pid' && $content) {
                $count = $userObject->where(['pid' => $content, 'uid' => ['neq', $id]])->count();
                if ($count) {
                    return json($userObject->ajaxError(100, '', 'pid已存在'));
                }
            }
            $result = $user->save();
            if ($result !== false) {
                if ($act == 'boss_agent' && $content) {
                    model('User')->belongBoss($id, $id);
                }
                if ($act == 'partner_agent' && $content) {
                    model('User')->belongPartner($id, $id);
                }
                model('AdminLog')->adminLogAdd($user->uid . '-' . $user->nickname . ",$act-$content", 'edit', 'boss_agent');
            } else {
                return json($userObject->ajaxError(100, '', '设置失败'));
            }
            return json($userObject->ajaxError(0, $content));
        } else {
            return json($userObject->ajaxError(2));
        }
    }

    /**
     * 分佣设置
     */
    public function commission() {
        $commissions = db('shop_config')->where(['code' => ['in', ['first_agent_commission', 'second_agent_commission', 'boss_agent_commission', 'boss_agent_order_commission']]])->column('value', 'code');
        $this->assign('commissions', $commissions);
        $this->assign('ur_here', '分佣设置');
        return $this->display();
    }

    public function saveCommission() {
        if (!\think\Validate::token('__token__', '', $_POST)) {
            return $this->url_redirect('非法数据来源', url("editAction"), "登录页");
        }
        $first_agent_commission = input('first_agent_commission/d', 0);
        $second_agent_commission = input('second_agent_commission/d', 0);
        $boss_agent_commission = input('boss_agent_commission/d', 0);
        $boss_agent_order_commission = input('boss_agent_order_commission/d', 0);

        db('shop_config')->where(['code' => 'first_agent_commission'])->update(['value' => $first_agent_commission]);
        db('shop_config')->where(['code' => 'second_agent_commission'])->update(['value' => $second_agent_commission]);
        db('shop_config')->where(['code' => 'boss_agent_commission'])->update(['value' => $boss_agent_commission]);
        db('shop_config')->where(['code' => 'boss_agent_order_commission'])->update(['value' => $boss_agent_order_commission]);
        \lib\cache\CacheTool::configsCache(['first_agent_commission', 'second_agent_commission', 'boss_agent_commission', 'boss_agent_order_commission']);
        return $this->url_redirect("设置完成请检查", "commission", "分佣设置");
    }

}
