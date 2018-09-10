<?php
namespace app\index\controller;

use app\common\model\UserPointLog;
use app\logic\GoodsLogic;
use think\Result;

/**
 * @author jiang
 */
class Activity extends Base {
    
    public function fiveRedpack(){
        $data = array();
        $data['status'] = 1;
        $redpack = UserPointLog::where(['uid'=>$this->uid,'type'=>'redpack','about_id'=>'1'])->find();
        if($redpack){
            $data['received'] = 1;
        }else{
            $data['received'] = 0;
        }
        $userinfo = model('User')->get($this->uid);
        $data['money'] = $userinfo->total_point/100;
        $point_log = UserPointLog::where(['uid'=>$this->uid])->field("round(sum(point)/100,2) as money,type,content")->group('type')->order('create_time desc')->select()->toArray();
        foreach($point_log as $k=>$v){
            $point_log[$k]['desc'] = UserPointLog::$pointTypeList[$v['type']]?:$v['content'];
            $point_log[$k]['img'] = "/static/index/img/".(UserPointLog::$pointImgList[$v['type']]?UserPointLog::$pointImgList[$v['type']]:'point_common.png');
        }
        $data['point_log'] = $point_log;
        $wechatShare = $this->wechatShare;
        $wechatShare['shareTitle'] = "【恭喜发财】送你一个红包";
        $wechatShare['shareDesc'] = "奖购-用购物的钱来中奖";
        $wechatShare['shareImg'] = SERVER_PATH."/static/index/img/redpack.png";
        $data['wechat_share'] = $wechatShare;
        $data['goods_list'] = GoodsLogic::instance()->getTop100();;
        Result::instance()->data($data)->success('获取成功')->output();
    }
    
    public function receiveRedpack(){
        $redpack = UserPointLog::where(['uid'=>$this->uid,'type'=>'redpack','about_id'=>'1'])->find();
        if($redpack){
            Result::instance()->fail('您已领取红包')->output();
        }
        $point = mt_rand(300, 600);
        $pointLog = new UserPointLog;
        $pointLog->uid = $this->uid;
        $pointLog->point = $point;
        $pointLog->content = "3~6元红包大派送";
        $pointLog->type = "redpack";
        $pointLog->about_id = 1;
        $pointLog->save();
        \app\common\model\User::find($this->uid)->save(['point' => ['exp', 'point+' . $point], 'enabled_point' => ['exp', 'enabled_point+' . $point], 'total_point' => ['exp', 'total_point+' . $point]]);
        $userinfo = model('User')->get($this->uid);
        $data['redpack'] = $point/100;
        $data['money'] = $userinfo->total_point/100;
        $point_log = UserPointLog::where(['uid'=>$this->uid])->field("round(sum(point)/100,2) as money,type")->group('type')->order('create_time desc')->select()->toArray();
        foreach($point_log as $k=>$v){
            $point_log[$k]['desc'] = UserPointLog::$pointTypeList[$v['type']]?:$v['content'];
            $point_log[$k]['img'] = "/static/index/img/".(UserPointLog::$pointImgList[$v['type']]?UserPointLog::$pointImgList[$v['type']]:'point_common.png');
        }
        $data['point_log'] = $point_log;
        Result::instance()->data($data)->success('领取成功')->output();
    }
    
    public function wxShare(){
        $wechatShare = $this->wechatShare;
        $wechatShare['shareUrl'] = SERVER_PATH."/?inviter=".$this->uid;
//        $wechatShare['shareTitle'] = "【恭喜发财】送你一个红包";
//        $wechatShare['shareDesc'] = "奖购-用购物的钱来中奖";
//        $wechatShare['shareImg'] = SERVER_PATH."/static/index/img/redpack.png";
        $data['wechat_share'] = $wechatShare;
        Result::instance()->data($data)->success('获取成功')->output();
    }
}