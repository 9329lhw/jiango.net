<?php
namespace app\tebiedemingzi\model;

use app\common\model\UserRelation;

class User extends Base
{
    protected $updateTime = false;
    
    public static function getUserInfo($id){
        return User::where(['uid'=>$id])->find();
    }
    
    public static function getUserToken($api_token){
        return User::where(['api_token'=>$api_token])->find();
    }
    
    /**
     * 设置代言人后归属用户
     * @author jiang
     */
    public static function belongBoss($uid,$boss_uid){
        $boss = UserRelation::where('uid',$boss_uid)->find();
        if($boss->boss_agent_uid){
            $boss->boss_agent_uid = $boss_uid;
            $boss->save();
        }
        //好友
        $second_friend = UserRelation::alias('ur')->field('ur.uid')->join('user u','u.uid=ur.uid')->where(['ur.second_agent_uid'=>$uid,'u.boss_agent'=>0])->select();
        UserRelation::alias('ur')->join('user u','u.uid=ur.uid')->where(['ur.second_agent_uid'=>$uid,'u.boss_agent'=>0])->update(['ur.boss_agent_uid'=>$boss_uid]);
        foreach($second_friend as $v){
            self::belongBoss($v['uid'],$boss_uid);
        }
    }
    
    /**
     * 设置合伙人后归属用户
     * @author jiang
     */
    public static function belongPartner($uid,$partner_uid){
        $boss = UserRelation::where('uid',$partner_uid)->find();
        if($boss->partner_agent_uid){
            $boss->partner_agent_uid = $partner_uid;
            $boss->save();
        }
        //好友
        $second_friend = UserRelation::alias('ur')->field('ur.uid')->join('user u','u.uid=ur.uid')->where(['ur.second_agent_uid'=>$uid,'u.partner_agent'=>0])->select();
        UserRelation::alias('ur')->join('user u','u.uid=ur.uid')->where(['ur.second_agent_uid'=>$uid,'u.partner_agent'=>0])->update(['ur.partner_agent_uid'=>$partner_uid]);
        foreach($second_friend as $v){
            self::belongPartner($v['uid'],$partner_uid);
        }
    }
   
}
