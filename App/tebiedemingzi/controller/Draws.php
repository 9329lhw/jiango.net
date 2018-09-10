<?php

namespace app\tebiedemingzi\controller;

class Draws extends Base {

    protected $point_rate = 100;
    protected $msg = [
        1=>'账号信息不对',
        2=>'数据信息不对',
        3=>'用户刷的数据',
        4=>'其他原因'
    ];
    protected $status = [
        0=>'请选择提现状态',
        1=>'等待审核中',
        2=>'审核完成',
        3=>'审核被拒',
        4=>'转账成功',
        5=>'转账失败'
    ];
    protected $desc = [
        1=>'审核通过',
        2=>'审核被拒',
        3=>'转账成功',
        4=>'转账失败'
    ];
    
    public function _initialize() {
        parent::_initialize();
        $this->assign('lang', $GLOBALS['_LANG']);
    }

    public function index() {
        
        if(!empty(input('nickname'))){
            $where['u.nickname'] = input('nickname');
        }
        if (input('status') > 0) {
            $where['d.status'] = input('status') - 1;
        }
        $actionobj = model('Draws');
        $draws_list = $actionobj->alias('d')->field('u.nickname,d.*')->join('user u','d.uid=u.uid')->where($where)->order('create_time desc')->paginate($this->pageSize);
        $this->assign('status_list',$this->status);
        $this->assign('page', $draws_list->render());
        $this->assign('drawslist', $draws_list);
        $this->assign('ur_here', '提现管理');
        $this->assign('action_link', array('text' => '提现日志', 'href' => '/tebiedemingzi/draws/drawLogs'));
        return $this->display();
    }

    public function editDraws() {
        $amount = input('amount');
        $uid = input('uid');
        $status = input('status');
        $id = input('id');
        $draw_status = model('Draws')->where(['id' => $id])->value('status');
        if ($draw_status == 1) {
            return $this->url_redirect('已经审核通过无需再进行审核', "/tebiedemingzi/draws/index", "提现管理");
        }
        $data['id'] = $id;
        $data['status'] = $status;
        $result = model('Draws')->saveData($data);

//        if ($status == 1) {
//            $userinfo = model('User')->getUserInfo($uid);
//            //更新用户积分
//            $userpoint = [
//                'point' => ($userinfo['point'] - $amount * $this->point_rate),
//                'enabled_point' => ($userinfo['enabled_point'] - $amount * $this->point_rate)
//            ];
//
//            model('User')->where(['uid' => $uid])->update($userpoint);
//            //添加积分日志
//            $pointlogs = [
//                'uid' => $uid,
//                'content' => '用户提现' . $amount . '元扣除积分',
//                'type' => 'aplay',
//                'point' => - $amount * $this->point_rate,
//            ];
//            model("UserPointLog")->save($pointlogs);
//        }
        return $this->url_redirect($result, "/tebiedemingzi/draws/index", "提现管理");
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
        $desc = '';
        $draw_status = model('Draws')->where(['id' => $id])->value('status');
        if($draw_status == $status){
            $date = [
                'code' => 400,
                'msg' => '重复操作'
            ];
            return json_encode($date);
        }
        $data['id'] = $id;
        $data['status'] = $status;
        
        if($status){
          $desc = $this->desc[$status];
        }
        if (input('msg_id') != 0) {
            $desc .= ','.$this->msg[input('msg_id')];
        }
        $data['desc'] = $desc;
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
                    'about_id' => $id,
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
