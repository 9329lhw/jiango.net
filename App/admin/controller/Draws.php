<?php

namespace app\admin\controller;

class Draws extends Base {

    protected $point_rate = 100;
    protected $msg = [
        1=>'账号信息不对',
        2=>'数据信息不对',
        3=>'用户刷的数据',
        4=>'其他原因'
    ];
    
    public function _initialize() {
        parent::_initialize();
        $this->assign('lang', $GLOBALS['_LANG']);
    }

    public function index() {
        $actionobj = model('Draws');
        $draws_list = $actionobj->getDrawsList($this->pageSize);
        $this->assign('page', $draws_list->render());
        $this->assign('drawslist', $draws_list);
        $this->assign('ur_here', '提现管理');
        $this->assign('action_link', array('text' => '提现日志', 'href' => '/admin/draws/drawLogs'));
        return $this->display();
    }

    public function editDraws() {
        $amount = input('amount');
        $uid = input('uid');
        $status = input('status');
        $id = input('id');
        $draw_status = model('Draws')->where(['id' => $id])->value('status');
        if ($draw_status == 1) {
            return $this->url_redirect('已经审核通过无需再进行审核', "/admin/draws/index", "提现管理");
        }
        $data['id'] = $id;
        $data['status'] = $status;
        $result = model('Draws')->saveData($data);

        if ($status == 1) {
            $userinfo = model('User')->getUserInfo($uid);
            //更新用户积分
            $userpoint = [
                'point' => ($userinfo['point'] - $amount * $this->point_rate),
                'enabled_point' => ($userinfo['enabled_point'] - $amount * $this->point_rate)
            ];

            model('User')->where(['uid' => $uid])->update($userpoint);
            //添加积分日志
            $pointlogs = [
                'uid' => $uid,
                'content' => '用户提现' . $amount . '元扣除积分',
                'type' => 'aplay',
                'point' => - $amount * $this->point_rate,
            ];
            model("UserPointLog")->save($pointlogs);
        }
        return $this->url_redirect($result, "/admin/draws/index", "提现管理");
    }

    public function drawLogs() {
        $this->assign('drawslist', []);
        $this->assign('ur_here', '提现日志');
        return $this->display();
    }

    public function check() {
        $id = input('id');
        $this->assign('id', $id);
        return $this->display();
    }

    public function transfer() {
        $id = input('id');
        $drawsinfo = model('Draws')->where(['id' => $id])->find();
        $this->assign('drawsinfo', $drawsinfo);
        return $this->display();
    }

    public function edit() {
        $status = input('status');
        $id = input('id');
        
        $data['id'] = $id;
        $data['status'] = $status;

        if (input('msg_id') != 0) {
            $data['desc'] = $this->msg[input('msg_id')];
        }
        $result = model('Draws')->saveData($data);
        if ($result) {
            if ($status == 3) {
                $drawsinfo = model('Draws')->where(['id' => $id])->find();
                $userinfo = model('User')->getUserInfo($drawsinfo['uid']);
                //更新用户积分
                $userpoint = [
                    'point' => ($userinfo['point'] - $drawsinfo['amount'] * $this->point_rate),
                    'enabled_point' => ($userinfo['enabled_point'] - $drawsinfo['amount'] * $this->point_rate)
                ];

                model('User')->where(['uid' => $drawsinfo['uid']])->update($userpoint);
                //添加积分日志
                $pointlogs = [
                    'uid' => $drawsinfo['uid'],
                    'content' => '用户提现' . $drawsinfo['amount'] . '元扣除积分',
                    'type' => 'aplay',
                    'point' => - $drawsinfo['amount'] * $this->point_rate,
                ];
                model("UserPointLog")->save($pointlogs);
            } else {
                $date = [
                    'code' => 200,
                    'msg' => '成功'
                ];
            }
            $date = [
                    'code' => 200,
                    'msg' => '成功'
                ];
        } else {
            $date = [
                'code' => 400,
                'msg' => '失败'
            ];
        }
        return json_encode($date);
    }

}
