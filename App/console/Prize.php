<?php

namespace app\console;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * @author jiang
 * php /var/www/jianggo.net/think prize --t 
 */
class Prize extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('prize')
            ->addOption('type', 't', Option::VALUE_OPTIONAL, 'type', 'userNotice')
            ->setDescription('Prize');
    }

    protected function execute(Input $input, Output $output)
    {
        $type  = $input->getOption('type');
        switch ($type){
            case 'userNotice'://开奖用户通知
                $prize_day = date("Y-m-d",strtotime('-1 days'));
                $prize = model('Prize')->where(['prize_day'=>$prize_day,'status'=>1])->find();
                if(!$prize){
                    exit;
                }
                if($prize){
                    $wechatObj = new \wechat\WechatApi();
                    $unprize_users = db('tb_orders')->alias('o')->field("o.trade_id,o.prize_status,ue.val as openid")->join('user_extension ue',"ue.uid=o.uid and ue.extension='openid' and ue.key='".$wechatObj->getAppid()."'")->where(['prize_date'=>$prize_day,'prize_status'=>['in',[2]]])->select();
                    foreach ($unprize_users as $k=>$v){
                        $data = [
                            'first' => ['value' => '您好，您参与的'.$prize['prize_day'].'奖期已开奖！', 'color' => '#3a5fad'],
                            'keyword1' => ['value' => $v['trade_id'], 'color' => '#3a5fad'],
                            'keyword2' => ['value' => '未中奖', 'color' => '#3a5fad'],
                            'keyword3' => ['value' => '￥0', 'color' => '#3a5fad'],
                            'keyword4' => ['value' => $prize['publish_day'].' 9:00:00', 'color' => '#3a5fad'],
                            'keyword5' => ['value' => $prize['winning_data'], 'color' => '#3a5fad'],
                            'remark' => ['value' => '更多详情请下载APP', 'color' => '#3a5fad']
                        ];
                        $wechatObj->sendTemplateMsg($v['openid'], '5kRXwnlHXIzVj85yAilIs3jB7RJzTj9fqWy-lyFZ0f0', $data, SERVER_PATH."/prize?date=".$prize_day );
//                        $wechatObj->sendTemplateMsg($v['openid'], 'NNAvgy7xqeln3FQdQRhupJs1b2ibnAgjow_P2kOOlkg',$data,SERVER_PATH."/prize?date=".$prize_day);
//                        $data = [
//                            'first'=>['value'=>'您好，您参与的奖期已开奖！','color'=>'#3a5fad'],
//                            'keyword1'=>['value'=>date('Ymd',strtotime($prize['prize_day'])),'color'=>'#3a5fad'],
//                            'keyword2'=>['value'=>$v['trade_id'],'color'=>'#3a5fad'],
//                            'keyword3'=>['value'=>  $v['prize_status'] == 1?'中奖':'未中奖','color'=>'#3a5fad'],
//                            'keyword4'=>['value'=>date('Y年m月d日',strtotime($prize['publish_day'])),'color'=>'#3a5fad'],
//                            'remark'=>['value'=>'更多详情请下载APP','color'=>'#3a5fad']
//                        ];
//                        $wechatObj->sendTemplateMsg($v['openid'], '1dvvTHVN9AzyBoiVW06AcSEKmsvjtuHOfeA_I0F2218',$data,SERVER_PATH."/prize?date=".$prize_day);
                    }
                    $winners = db('winner')->alias('w')->field("w.trade_id,w.type,w.point,ue.val as openid")->join('user_extension ue',"ue.uid=w.uid and ue.extension='openid' and ue.key='".$wechatObj->getAppid()."'")->where(['day'=>$prize_day,'oid'=>['gt',0]])->select();
                    foreach ($winners as $k=>$v){
                        $data = [
                            'first' => ['value' => '您好，您参与的'.$prize['prize_day'].'奖期已开奖！', 'color' => '#3a5fad'],
                            'keyword1' => ['value' => $v['trade_id'], 'color' => '#3a5fad'],
                            'keyword2' => ['value' => $v['type'] == 'order'?'中奖':'好友中奖', 'color' => '#3a5fad'],
                            'keyword3' => ['value' => '￥'.$v['point']/100, 'color' => '#3a5fad'],
                            'keyword4' => ['value' => $prize['publish_day'].' 9:00:00', 'color' => '#3a5fad'],
                            'keyword5' => ['value' => $prize['winning_data'], 'color' => '#3a5fad'],
                            'remark' => ['value' => '更多详情请下载APP', 'color' => '#3a5fad']
                        ];
                        $wechatObj->sendTemplateMsg($v['openid'], '5kRXwnlHXIzVj85yAilIs3jB7RJzTj9fqWy-lyFZ0f0', $data, SERVER_PATH."/prize?date=".$prize_day );
                    }
                }
                break;
            case 'adminNotice'://通知运营者开奖
                $prize_day = date("Y-m-d",strtotime('-1 days'));
                $prize = model('Prize')->where(['prize_day'=>$prize_day,'status'=>1])->find();
                if(!$prize || !$prize['status']){
                    $wechatObj = new \wechat\WechatApi();
                    $wechatObj->send_wxmsg('','text','亲，注意开奖时间哦');
                }
                break;
        }
        $output->writeln("Successed");
    }
}

