<?php
namespace app\console;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * @author jiang
 * php /var/www/jianggo.net/think --type updateGoods
 */
class Goods extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('goods')
            ->addOption('type', 't', Option::VALUE_OPTIONAL, 'type', 'update')
            ->addOption('page', 'p', Option::VALUE_OPTIONAL, 'page', 1)    
            ->setDescription('Goods');
    }

    protected function execute(Input $input, Output $output)
    {
        $type  = $input->getOption('type');
        $page  = $input->getOption('page');
        switch ($type){
            case 'update':
                \app\logic\DtkGoodsLogic::instance()->update($page);
                break;
            case 'clear':
                \app\logic\DtkGoodsLogic::instance()->clear();
                break;
            case 'queue':
                \app\logic\DtkGoodsLogic::instance()->goods_queue();
                break;
            case 'tb_coupon_queue':
                \app\logic\DtkGoodsLogic::instance()->tb_coupon_queue();
                break;
            case 'hpt_update':
                \app\logic\DtkGoodsLogic::instance()->hpt_update($page);
                break;
            case 'tkjd_update':
                \app\logic\DtkGoodsLogic::instance()->tkjd_update($page);
                break;
        }
        $output->writeln("Successed");
    }
}

