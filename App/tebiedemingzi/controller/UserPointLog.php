<?php

namespace app\tebiedemingzi\controller;

/**
 * 积分
 * @author jiang
 */
class UserPointLog extends Base {
    
    private $typeList = array('all'=>'全部','invite_second'=>'邀请用户','invite_first'=>'下级邀请用户','invite_boss'=>'特殊邀请','prize'=>'中奖积分','failure_prize'=>'中奖失效');


    public function index(){
        $uid = input('uid/d',0);
        $where['uid'] = $uid;
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
        if(is_numeric(input('type'))){
            $where['type'] = input('type');
        }
        if(is_numeric(input('inc_dec'))){
            if(input('inc_dec')){
                $where['point'] = ['gt',0];
            }else{
                $where['point'] = ['lt',0];
            }
        }
        $list = model('UserPointLog')->where($where)->order("$sort_by $sort_order")->paginate($this->pageSize);
        $this->assign('list', $list);
        $this->assign('type_list', $this->typeList);
        $this->assign('inc_dec_list', ['all'=>'全部','1'=>'收入','0'=>'支出']);
        $this->assign('page', $list->render());
        return $this->display();
    }
}
