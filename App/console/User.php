<?php

namespace app\console;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use app\logic\UserLogic;

/**
 * @author jiang
 * php /var/www/jianggo.net/think user --t 
 */
class User extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('user')
            ->addOption('type', 't', Option::VALUE_OPTIONAL, 'type', 'point')
            ->setDescription('User');
    }

    protected function execute(Input $input, Output $output)
    {
        $type  = $input->getOption('type');
        switch ($type){
            case 'point'://计算用户积分
                $user = model('UserPointLog')->group('uid')->column('uid');
                $boss_user = model('TbOrders')->where(['boss_agent_uid'=>['gt',0]])->group('boss_agent_uid')->column('boss_agent_uid');
                $user = array_unique(array_merge($user,$boss_user));
                $partner_user = model('TbOrders')->where(['partner_agent_uid'=>['gt',0]])->group('partner_agent_uid')->column('partner_agent_uid');
                $user = array_unique(array_merge($user,$partner_user));
                foreach($user as $u){
                    UserLogic::instance()->pointStatistics($u);
                }
                break;
        }
        $output->writeln("Successed");
    }
}

