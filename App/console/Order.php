<?php
namespace app\console;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use app\logic\TbOrdersLogic;
use app\logic\CrontabLogic;
use app\common\model\TbOrders;

/**
 * @author jiang
 * php /var/www/jianggo.net/think order --type import
 * php /var/www/jianggo.net/think order -t import
 */
class Order extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('order')
            ->addOption('type', 't', Option::VALUE_OPTIONAL, 'type', 'import')
            ->setDescription('Order');
    }

    protected function execute(Input $input, Output $output)
    {
        $type  = $input->getOption('type');
        switch ($type){
            case 'import'://大批量订单同步
                $last_import_time = cache('crontab_order_import_time');
                $filectime = filectime('/data/jianggo/TaokeDetail.xls');
                if(!$last_import_time || ($filectime && $last_import_time<$filectime)){
                    cache('crontab_order_import_time',filectime('/data/jianggo/TaokeDetail.xls'));
                    TbOrdersLogic::instance()->import('/data/jianggo/TaokeDetail.xls');
                }
                break;
            case 'bossSettle'://代言人订单操作
                TbOrdersLogic::instance()->bossSettle();
                break;
            case 'partnerSettle'://代言人订单操作
                TbOrdersLogic::instance()->partnerSettle();
                break;
            case 'updateOrderPic'://完善订单商品图片
                CrontabLogic::instance()->updateOrderPic();
                break;
            case 'trackOperation'://订单追踪处理
                \app\common\model\TbOrders::where(['status'=>0,'led_time'=>['lt',strtotime('-2 days')]])->update(['status'=>-1]);
                break;
            case 'affiliate'://订单归属处理
                $order = \app\common\model\TbOrders::alias('o')->field('o.*')->join('user_extension ur',"ur.extension='tbID' and ur.val=right(o.trade_id,6)")->where(['o.status'=>['gt',0],'o.uid'=>0,'o.create_time'=>['gt',strtotime(date('Y-m-d',time()))]])->select();
                foreach($order as $o){
                    $tbID = substr($o->trade_id,-6);
                    $user = \app\common\model\UserExtension::where('val',$tbID)->select();
                    if($user && count($user) == 1){
                        $o->uid = $user[0]['uid'];
                        $o->save();
                    }
                }
                break;
            case 'rebateNotice':
                $start_time = strtotime(date('Y-m-d',strtotime('-5 days')));
                $end_time = $start_time+86400;
                $time = 1526832000;
                $start_time = $start_time>$time?$start_time:$time;
                $where['o.uid'] = ['gt',0];
                $where['status'] = ['in',[3,12,14]];
                $where['create_time'] = ['between',[$start_time,$end_time]];
                $rebate_order = TbOrders::alias('o')->field('ur.uid,ur.first_agent_uid,ur.second_agent_uid,o.total_forecast_income,o.trade_id,o.oid')
                    ->join('user_relation ur','ur.uid=o.uid')
                    ->where($where)->order('o.create_time')->select()->toArray();
                $rebater = array();
                foreach($rebate_order as $o){
                    if(!$rebater[$o['uid']]){
                        $rebate = db('tb_order_extension')->where(['oid'=>$o['oid'],'extension'=>'order_rebate'])->find();
                        if(!$rebate){
                            $rebater[$o['uid']] = 1;
                        }
                    }
                    if($o['second_agent_uid'] && !$rebater[$o['second_agent_uid']]){
                        $rebate = db('tb_order_extension')->where(['oid'=>$o['oid'],'extension'=>'order_rebate_second'])->find();
                        if(!$rebate){
                            $rebater[$o['second_agent_uid']] = 1;
                        }
                    }
                    if($o['first_agent_uid'] && !$rebater[$o['first_agent_uid']]){
                        $rebate = db('tb_order_extension')->where(['oid'=>$o['oid'],'extension'=>'order_rebate_first'])->find();
                        if(!$rebate){
                            $rebater[$o['first_agent_uid']] = 1;
                        }
                    }
                }
				
				$wechatObj = new \wechat\WechatApi();
                foreach(array_keys($rebater) as $v){
					if($v == 2 || $v == 10 || $v == 12){
						continue;
					}
                    $userExt = db('UserExtension')->where(['uid' => $v, 'extension' => 'openid', 'key' => $wechatObj->getAppid()])->find();
                    if($userExt){
                        $wechatObj->send_wxmsg($userExt['val'],'text', '您有即将失效的订单红包机会，请尽快前往订单红包抽取！');
                    }
                }
                break;
        }
        $output->writeln("Successed");
    }
}

