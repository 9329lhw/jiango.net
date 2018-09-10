<?php
namespace app\common\model;

/**
 * 后台管理员日志
 */
class UserPointLog extends \think\Model{
    
    protected $updateTime = false;
    protected $dateFormat = 'Y-m-d H:i';
    public static $pointTypeList = array('all'=>'全部','subscribe'=>'关注','invite_second'=>'邀请用户','invite_first'=>'好友邀请用户','invite_boss'=>'代言人邀请','prize'=>'中奖积分','failure_prize'=>'中奖失效','aplay'=>'提现','sign_in'=>'签到','redpack'=>'红包','order_rebate_second'=>'好友订单红包','order_rebate_first'=>'好友订单红包','order_rebate'=>'订单红包','failure_order_rebate_second'=>'订单红包失效','failure_order_rebate_first'=>'订单红包失效','failure_order_rebate'=>'订单红包失效');
    public static $pointImgList = array('all'=>'point_common.png','subscribe'=>'point_common.png','invite_second'=>'point_invite.png','invite_first'=>'point_invite.png','invite_boss'=>'point_invite.png','prize'=>'point_prize.png','failure_prize'=>'point_failure_prize.png','aplay'=>'point_aplay.png','sign_in'=>'point_sign_in.png','redpack'=>'point_redpack.png');
    
}