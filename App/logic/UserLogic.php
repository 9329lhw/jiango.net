<?php

namespace app\logic;

use think\Base;
use app\common\model\User;
use app\common\model\UserRelation;
use think\Cache;
use app\common\model\UserPointLog;
use app\common\model\TbOrders;
use think\Result;

class UserLogic extends Base {

    public static $accessTokenPrefix = 'accessToken_';
    public static $accessTokenAndClientPrefix = 'accessTokenAndClient_';

    /**
     * 登录
     * @author jiang
     */
    public function register($userinfo) {
        $userinfo['nickname'] = filterNickname($userinfo['nickname']);
        logResult(input('server.SERVER_NAME').input('server.REQUEST_URI'), "register");
        logResult(json_encode($userinfo), "register");
        $user = new User;
        $user->unionid = $userinfo['unionid'];
        $user->nickname = $userinfo['nickname'];
        $user->sex = $userinfo['sex'];
        $user->headimgurl = $userinfo['headimgurl'];
        $user->create_time = time();
        $user->boss_agent = 0;
        $user->partner_agent = 0;
        $user->source = input('server.SERVER_NAME').input('server.REQUEST_URI');
        $register = $user->save();
        if (!$register) {
            return false;
        }
        $userRelation = model('UserRelation');
        $userRelation->uid = $user->uid;
        if ($userinfo['inviter']) {
            $wechatObj = new \wechat\WechatApi('wx4a7d29be47719738','2b46b4a949462a0013a2c3ab8cc59d37');
            $inviter = \app\common\model\User::alias('u')->field('u.*,ur.first_agent_uid,ur.second_agent_uid,ur.boss_agent_uid,ur.partner_agent_uid')->join('user_relation ur', 'u.uid=ur.uid', 'left')->where(['u.uid' => $userinfo['inviter']])->find();
            if ($inviter) {
                $commission = \lib\cache\CacheTool::configsCache(['first_agent_commission', 'second_agent_commission', 'boss_agent_commission']);
                $userRelation->second_agent_uid = $inviter->uid;
                $userRelation->first_agent_uid = $inviter->second_agent_uid ? $inviter->second_agent_uid : 0;
                $boss_uid = $inviter->boss_agent ? $inviter->uid : $inviter->boss_agent_uid;
                if($boss_uid){
                    $boss_agent = \app\common\model\User::where(['uid' => $boss_uid, 'boss_agent' => 1])->find();
                    if ($boss_agent) {
                        $userRelation->boss_agent_uid = $boss_agent->uid;
                        if($boss_uid != $userRelation->second_agent_uid && $boss_uid != $userRelation->first_agent_uid){
                            //积分邀请奖励条数
                            $point_count = UserPointLog::where(['uid'=>$boss_agent->uid,'type'=>['in',['invite_first','invite_second','invite_boss']]])->count();
                            if($point_count < 5000){
                                $pointLog = new UserPointLog;
                                $pointLog->uid = $boss_agent->uid;
                                $pointLog->point = $commission['boss_agent_commission'];
                                $pointLog->content = "好友邀请用户获取代言人积分，用户：" . $user->uid . '-' . $user->nickname;
                                $pointLog->type = "invite_boss";
                                $pointLog->about_id = $user->uid;
                                $pointLog->save();
                                User::find($boss_agent->uid)->save(['point' => ['exp', 'point+' . $commission['boss_agent_commission']], 'enabled_point' => ['exp', 'enabled_point+' . $commission['boss_agent_commission']], 'total_point' => ['exp', 'total_point+' . $commission['boss_agent_commission']]]);
                                $userExt = db('UserExtension')->where(['uid' => $boss_agent->uid, 'extension' => 'openid', 'key' => $wechatObj->getAppid()])->find();
                                if($userExt){
                                    $wechatObj->send_wxmsg($userExt['val'],'text', '您的好友成功邀请好友【'.$user->nickname.'】，恭喜您获取代言人奖励【'.$commission['boss_agent_commission'].'】积分');
                                }
                            }
                        }
                    }
                }
                $userRelation->partner_agent_uid = $inviter->partner_agent?$inviter->uid:($inviter->partner_agent_uid ? $inviter->partner_agent_uid : 0);
                $point_count = UserPointLog::where(['uid'=>$inviter->uid,'type'=>['in',['invite_first','invite_second','invite_boss']]])->count();
                if($point_count < 5000){
                    //邀请送积分
                    $pointLog = new UserPointLog;
                    $pointLog->uid = $inviter->uid;
                    $pointLog->point = $commission['second_agent_commission'];
                    $pointLog->content = "邀请用户获取积分，用户：" . $user->uid . '-' . $user->nickname;
                    $pointLog->type = "invite_second";
                    $pointLog->about_id = $user->uid;
                    $pointLog->save();
                    User::find($inviter->uid)->save(['point' => ['exp', 'point+' . $commission['second_agent_commission']], 'enabled_point' => ['exp', 'enabled_point+' . $commission['second_agent_commission']], 'total_point' => ['exp', 'total_point+' . $commission['second_agent_commission']], 'second_agent_num' => ['exp', 'second_agent_num+1']]);
                    $userExt = db('UserExtension')->where(['uid' => $inviter->uid, 'extension' => 'openid', 'key' => $wechatObj->getAppid()])->find();
                    if($userExt){
                        $wechatObj->send_wxmsg($userExt['val'],'text', '您已成功邀请好友【'.$user->nickname.'】，恭喜您获取好友邀请奖励【'.$commission['second_agent_commission'].'】积分');
                    }
                }
                if ($userRelation->first_agent_uid) {
                    $point_count = UserPointLog::where(['uid'=>$userRelation->first_agent_uid,'type'=>['in',['invite_first','invite_second','invite_boss']]])->count();
                    if($point_count < 5000){
                        $pointLog = new UserPointLog;
                        $pointLog->uid = $userRelation->first_agent_uid;
                        $pointLog->point = $commission['first_agent_commission'];
                        $pointLog->content = "好友邀请用户获取一级积分，用户：" . $user->uid . '-' . $user->nickname;
                        $pointLog->type = "invite_first";
                        $pointLog->about_id = $user->uid;
                        $pointLog->save();
                        User::find($userRelation->first_agent_uid)->save(['point' => ['exp', 'point+' . $commission['first_agent_commission']], 'enabled_point' => ['exp', 'enabled_point+' . $commission['first_agent_commission']], 'total_point' => ['exp', 'total_point+' . $commission['first_agent_commission']], 'first_agent_num' => ['exp', 'first_agent_num+1']]);
                        $userExt = db('UserExtension')->where(['uid' => $userRelation->first_agent_uid, 'extension' => 'openid', 'key' => $wechatObj->getAppid()])->find();
                        if($userExt){
                            $wechatObj->send_wxmsg($userExt['val'],'text', '您的好友成功邀请好友【'.$user->nickname.'】，恭喜您获取好友邀请奖励【'.$commission['first_agent_commission'].'】积分');
                        }
                    }
                }
            }
        }
        $userRelation->save();

        return $user;
    }

    public function update_token($uid) {
        //更新用户token
        $user_token = [
            'api_token' => md5(md5(uniqid()) . date('YmdHis')),
            'expire_time' => time() + 7 * 86400
        ];
        User::where(['uid' => $uid])->update($user_token);
        Cache::set(self::$accessTokenPrefix . $user_token['api_token'], $user_token['api_token'], $user_token['expire_time']);
        return $user_token['api_token'];
    }
    
    /**
     * 个人中心
     * @author jiang
     */
    public function center($uid) {
        $userinfo = model('User')->get($uid);
        if (empty($userinfo)) {
            return false;
        }
        $data['uid'] = $userinfo->uid;
        $data['unionid'] = $userinfo->unionid;
        $data['nickname'] = $userinfo->nickname;
        $data['sex'] = $userinfo->sex;
        $data['headimgurl'] = $userinfo->headimgurl;
        $data['point'] = $userinfo->point;
        $data['partner_agent'] = $userinfo->partner_agent;
        $data['partner']['complete_earn'] = 0;
        $data['partner']['pending_earn'] = 0;
        $data['partner']['team_num'] = 0;
        $data['partner']['boss_num'] = 0;
        if($data['partner_agent']){
            $data['partner']['complete_earn'] = TbOrders::where(['partner_agent_uid'=>$uid])->sum('partner_agent_commission');
            $data['partner']['complete_earn'] = $data['partner']['complete_earn']/100;
            $data['partner']['pending_earn'] = TbOrders::where(['partner_agent_uid'=>$uid,'total_income'=>0])->sum('floor(total_forecast_income*partner_agent_rate)');//待完成预估收益
            $data['partner']['pending_earn'] = $data['partner']['pending_earn']/100;
            $data['partner']['team_num'] = UserRelation::where('partner_agent_uid',$userinfo->uid)->count();
            $data['partner']['boss_num'] = UserRelation::alias('ur')->join('user u','u.uid=ur.uid')->where(['ur.partner_agent_uid'=>$userinfo->uid,'boss_agent'=>['gt',0]])->count();
            if($userinfo->boss_agent){
                $boss_complete_earn = TbOrders::where(['boss_agent_uid'=>$uid])->sum('boss_agent_commission');//已完成收益
                $data['partner']['complete_earn'] += $boss_complete_earn/100;
                $boss_pending_earn = TbOrders::where(['boss_agent_uid'=>$uid,'total_income'=>0])->sum('floor(total_forecast_income*boss_agent_rate)');//待完成预估收益
                $data['partner']['pending_earn'] += $boss_pending_earn/100;
            }
        }
        $data['boss_agent'] = $userinfo->partner_agent?0:$userinfo->boss_agent;
        $data['boss']['complete_earn'] = 0;//已完成收益
        $data['boss']['pending_earn'] = 0;//待完成预估收益
        $data['boss']['team_num'] = 0;//团队人数
        if($data['boss_agent']){
            $data['boss']['complete_earn'] = TbOrders::where(['boss_agent_uid'=>$uid])->sum('boss_agent_commission');//已完成收益
            $data['boss']['complete_earn'] = $data['boss']['complete_earn']/100;
            $data['boss']['pending_earn'] = TbOrders::where(['boss_agent_uid'=>$uid,'total_income'=>0])->sum('floor(total_forecast_income*boss_agent_rate)');//待完成预估收益
            $data['boss']['pending_earn'] = $data['boss']['pending_earn']/100;
            $data['boss']['team_num'] = UserRelation::where('boss_agent_uid',$userinfo->uid)->count();
        }
        if(!empty($userinfo->aplay_number)){
           $data['aplay_number'] = 1;
        }else{
            $data['aplay_number'] = 0;
        }
        $data['total_point'] = $userinfo->total_point;
        $data['total_point_money'] = $data['total_point']/100;
//        $data['enabled_point'] = $userinfo->enabled_point;
        $data['invite']['first_num'] = $userinfo->first_agent_num;
        $data['invite']['second_num'] = $userinfo->second_agent_num;
        $data['order']['led_num'] = model('TbOrders')->where(['uid'=>$uid,'status'=>0])->count();
        $data['order']['wait_prize_num'] = model('TbOrders')->where(['uid'=>$uid,'prize_status'=>99])->count();
        $data['order']['prize_num'] = model('TbOrders')->where(['uid'=>$uid,'prize_status'=>1])->count();
        return $data;
    }
    
    /**
     * 签到
     * @author jiang
     */
    public function signIn($uid){
        $date = date('Y-m-d 00:00:00');
        $time = strtotime($date);
        $sign = UserPointLog::where(['uid'=>$uid,'type'=>'sign_in','create_time'=>['between',[$time,$time+86400]]])->find();
        if($sign){
            return '今日已签到';
        }else{
            $point = \lib\cache\CacheTool::configsCache('sign_in_point');
            $pointLog = new UserPointLog;
            $pointLog->uid = $uid;
            $pointLog->point = $point;
            $pointLog->content = "签到送积分";
            $pointLog->type = "sign_in";
            $pointLog->about_id = 0;
            $pointLog->save();
            User::find($uid)->save(['point' => ['exp', 'point+' . $point], 'enabled_point' => ['exp', 'enabled_point+' . $point], 'total_point' => ['exp', 'total_point+' . $point]]);
            return true;
        }
    }
    
    /**
     * 好友列表
     * @author jiang
     */
    public function agentUser($uid,$type = 2,$page = 0,$pageSize = 10) {
        if($type == 1){
            $where['first_agent_uid'] = $uid;
            if(input('friend_uid/d',0)){
                $where['second_agent_uid'] = input('friend_uid/d',0);
            }
        }elseif($type == 2){
            $where['second_agent_uid'] = $uid;
        }
        if($page == 1){
            $user = model('User')->get($uid);
            $data['first_num'] = $user->first_agent_num;
            $data['second_num'] = $user->second_agent_num;
        }
        $count = model('User')->alias('u')->where($where)->join('user_relation ur','ur.uid=u.uid')->count();
        if($count){
            $list = model('User')->alias('u')->field('u.uid as friend_uid,u.nickname,u.second_agent_num as friend_num,u.headimgurl')->where($where)->join('user_relation ur','ur.uid=u.uid')->order('create_time desc')->limit(($page-1)*$pageSize.",$pageSize")->select();
        }else{
            $list = [];
        }
        $data['list'] = $list;
        $data['count'] = $count;
        $data['total_page'] = $page?ceil($data['count']/$pageSize):1;
        return $data;
    }
    
    /**
     * 代言人好友列表
     * @author jiang
     */
    public function bossUser($uid,$page = 0,$pageSize = 10) {
        $where['boss_agent_uid'] = $uid;
        $count = model('User')->alias('u')->where($where)->join('user_relation ur','ur.uid=u.uid')->count();
        if($count){
            $list = model('User')->alias('u')->field('u.uid as friend_uid,u.nickname,u.headimgurl')->where($where)->join('user_relation ur','ur.uid=u.uid')->order('create_time desc')->limit(($page-1)*$pageSize.",$pageSize")->select();
        }else{
            $list = [];
        }
        if($page == 1){
            $user = model('User')->get($uid);
            $data['first_num'] = $count;
            $data['second_num'] = $user->second_agent_num;
        }
        $data['list'] = $list;
        $data['count'] = $count;
        $data['total_page'] = $page?ceil($data['count']/$pageSize):1;
        return $data;
    }
    
    /**
     * 合伙人好友列表
     * @author jiang
     */
    public function partnerUser($uid,$type='boss',$page = 0,$pageSize = 10) {
        $where['partner_agent_uid'] = $uid;
        if($type == 'boss'){
            $where['u.boss_agent'] = ['gt',0];
        }
        $count = model('User')->alias('u')->where($where)->join('user_relation ur','ur.uid=u.uid')->count();
        if($count){
            $list = model('User')->alias('u')->field('u.uid as friend_uid,u.nickname,u.headimgurl')->where($where)->join('user_relation ur','ur.uid=u.uid')->order('create_time desc')->limit(($page-1)*$pageSize.",$pageSize")->select();
        }else{
            $list = [];
        }
        foreach($list as $k=>$v){
            $list[$k]['team_num'] = UserRelation::where('boss_agent_uid',$v['friend_uid'])->count();
        }
        if($page == 1){
            $data['team_num'] = UserRelation::where('partner_agent_uid',$uid)->count();
            $data['boss_num'] = UserRelation::alias('ur')->join('user u','u.uid=ur.uid')->where(['ur.partner_agent_uid'=>$uid,'boss_agent'=>['gt',0]])->count();
        }
        $data['list'] = $list;
        $data['count'] = $count;
        $data['total_page'] = $page?ceil($data['count']/$pageSize):1;
        return $data;
    }

    /**
     * 计算用户积分
     * @author jiang
     */
    public function pointStatistics($uid) {
        $user = User::find($uid);
        if (!$user) {
            return;
        }
        $total_point = UserPointLog::where(['uid' => $uid, 'type' => ['neq', 'aplay']])->sum('point');
        $point = UserPointLog::where(['uid' => $uid])->sum('point');
//        $unenabled_point = UserPointLog::where(['uid' => $uid, 'type' => 'prize', 'create_time' => ['gt', strtotime("-15 days")]])->sum('point'); //中奖不可提现部分
        $boss_point = \app\common\model\TbOrders::where(['boss_agent_uid' => $uid])->sum('boss_agent_commission'); //订单佣金
        $unenabled_boss_point = \app\common\model\TbOrders::where(['boss_agent_uid' => $uid, 'earning_time' => ['gt', strtotime("-15 days")]])->sum('boss_agent_commission'); //佣金不可提现部分
        $point += $boss_point;
        $total_point += $boss_point;
        $partner_point = \app\common\model\TbOrders::where(['partner_agent_uid' => $uid])->sum('partner_agent_commission'); //订单佣金
        $unenabled_partner_point = \app\common\model\TbOrders::where(['partner_agent_uid' => $uid, 'earning_time' => ['gt', strtotime("-15 days")]])->sum('partner_agent_commission'); //佣金不可提现部分
        $point += $partner_point;
        $total_point += $partner_point;
        $enabled_point = UserPointLog::where(['uid' => $uid, 'type' => ['not in', ['prize', 'failure_prize','order_rebate','order_rebate_second','order_rebate_first','failure_order_rebate','failure_order_rebate_second','failure_order_rebate_first']]])->sum('point');
        $rebate_point = UserPointLog::alias('upl')->join('tb_orders o', 'o.oid=upl.about_id')->where(['total_income'=>['gt',0],'o.earning_time' => ['between', [1,strtotime("-15 days")]],'upl.uid' => $uid, 'upl.type' => ['in', ['order_rebate','order_rebate_second','order_rebate_first','failure_order_rebate','failure_order_rebate_second','failure_order_rebate_first']]])->sum('point');
        $prize_point = UserPointLog::alias('upl')->join('winner w', 'w.uid=upl.uid and w.oid=upl.about_id and w.status=1')->join('tb_orders o', 'o.oid=w.oid')->where(['upl.uid' => $uid, 'upl.type' => 'prize', 'o.earning_time' => ['between', [1,strtotime("-15 days")]]])->sum('upl.point');
        $enabled_point = $enabled_point + $prize_point + $rebate_point + $boss_point - $unenabled_boss_point + $partner_point - $unenabled_partner_point;
        if ($user->total_point != $total_point || $user->point != $point || $user->enabled_point != $enabled_point) {
            $user->total_point = $total_point;
            $user->point = $point;
            $user->enabled_point = $enabled_point;
            $user->save();
        }
    }

    /**
     * 查询pid
     * @param type $uid
     * @return type
     */
    public function getPidByPid($pid) {
        return User::where(['pid' => $pid, 'boss_agent' => 1])->value("pid");
    }

    public function userRegister($user_name,$password){
        if (empty($user_name)) {
            Result::instance()->fail('用户名称不能为空')->output();
        }
        
        if (empty($password)) {
            Result::instance()->fail('密码不能为空')->output();
        }
        $info = model('CheckUsers')->where(['user_name'=>$user_name])->find();
        if(!empty($info)){
            Result::instance()->fail('该用户名已经被注册过')->output();
        }
        $uid = rand(1,15);
        $data = [
            'uid'=>$uid,
            'user_name'=>$user_name,
            'password'=>$password
        ];
        
        $result = model('CheckUsers')->create($data);
        if(!$result){
            Result::instance()->fail('注册失败')->output();
        }
        $user = model('User')->where(['uid'=>$uid])->find();
        $user->nickname = $user_name;
        $user->realname = $user_name;
        return $user;
    }
    
    public function userCheckLogin($user_name,$password){
        if (empty($user_name)) {
            Result::instance()->fail('用户名称不能为空')->output();
        }
        
        if (empty($password)) {
            Result::instance()->fail('密码不能为空')->output();
        }
        
        $info = model('CheckUsers')->where(['user_name'=>$user_name,'password'=>$password])->find();
        
        if(empty($info)){
            Result::instance()->fail('用户名或密码错误')->output();
        }
        
        $user = model('User')->where(['uid'=>$info['uid']])->find();
        $user->nickname = $user_name;
        $user->realname = $user_name;
        return $user;
    }
}
