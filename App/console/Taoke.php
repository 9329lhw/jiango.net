<?php

namespace app\console;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use app\logic\TbOrdersLogic;

/**
 * @author jiang
 * php /var/www/jianggo.net/think order --type import
 * php /var/www/jianggo.net/think order -t import
 */
class Taoke extends Command {

    protected function configure() {
        // 指令配置
        $this->setName('taoke')
                ->addOption('type', 't', Option::VALUE_OPTIONAL, 'type', 'updateTbOrders')
                ->addOption('days', 'days', Option::VALUE_OPTIONAL, 'days', '30')
                ->addOption('all', 'all', Option::VALUE_OPTIONAL, 'all', '0')
                ->setDescription('Taoke');
    }

    protected function execute(Input $input, Output $output) {
        $type = $input->getOption('type');
        switch ($type) {
            case 'updateDayTbOrders':
                $date = date('Y-m-d', time());
                header("Content-type:text/html;Charset=utf8");
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'http://pub.alimama.com/report/getTbkPaymentDetails.json?startTime=' . $date . '&endTime=' . $date . '&payStatus=&queryType=1&toPage=1&perPageSize=1&total=&t=1528344569153&pvid=&_tb_token_=e33683e78537e&_input_charset=utf-8');
                curl_setopt($ch, CURLOPT_COOKIE, file_get_contents('/data/jianggo/tk_cookies.txt'));
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $ret = curl_exec($ch);
                $ret = json_decode($ret, true);
                if ($ret['data']['paymentList'] && ($input->getOption('all') || strtotime($ret['data']['paymentList'][0]['createTime']) > strtotime('-25 minutes'))) {
                    curl_setopt($ch, CURLOPT_URL, 'http://pub.alimama.com/report/getTbkPaymentDetails.json?spm=a219t.7664554.1998457203.61.197d35d98kzlZB&queryType=1&payStatus=&DownloadID=DOWNLOAD_REPORT_INCOME_NEW&startTime=' . $date . '&endTime=' . $date);
                    $ret = curl_exec($ch);
                    file_put_contents('/data/jianggo/TaokeDayOrder' . date('Ymd') . '.xls', $ret);
                    TbOrdersLogic::instance()->import('/data/jianggo/TaokeDayOrder' . date('Ymd') . '.xls');
                }
                curl_close($ch);
                break;
            case 'updateTbOrders':
                $days = $input->getOption('days');
                header("Content-type:text/html;Charset=utf8");
                $ch = curl_init();
                $start_date = date('Y-m-d', strtotime('-' . $days . ' days'));
                $end_date = date('Y-m-d', time());
                curl_setopt($ch, CURLOPT_URL, 'http://pub.alimama.com/report/getTbkPaymentDetails.json?spm=a219t.7664554.1998457203.61.197d35d98kzlZB&queryType=1&payStatus=&DownloadID=DOWNLOAD_REPORT_INCOME_NEW&startTime=' . $start_date . '&endTime=' . $end_date);
                curl_setopt($ch, CURLOPT_COOKIE, file_get_contents('/data/jianggo/tk_cookies.txt'));
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $ret = curl_exec($ch);
                curl_close($ch);
                file_put_contents('/data/jianggo/TaokeDetail.xls', $ret);
                TbOrdersLogic::instance()->import('/data/jianggo/TaokeDetail.xls');
                break;
            case 'updateTbIsvOrders'://第三方服务商推广
                $days = $input->getOption('days');
                header("Content-type:text/html;Charset=utf8");
                $ch = curl_init();
                $start_date = date('Y-m-d', strtotime('-3 days'));
                $end_date = date('Y-m-d', time());
                curl_setopt($ch, CURLOPT_URL, 'http://pub.alimama.com/report/getTbkThirdPaymentDetails.json?queryType=2&payStatus=&DownloadID=DOWNLOAD_REPORT_TK3_PUB&startTime=' . $start_date . '&endTime=' . $end_date);
                curl_setopt($ch, CURLOPT_COOKIE, file_get_contents('/data/jianggo/tk_cookies.txt'));
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $ret = curl_exec($ch);
                curl_close($ch);
                file_put_contents('/data/jianggo/TaokeIsvDetail.xls', $ret);
                TbOrdersLogic::instance()->import('/data/jianggo/TaokeIsvDetail.xls', false);
                break;
            case 'refundTbOrders':
                header("Content-type:text/html;Charset=utf8");
                $ch = curl_init();
                $start_date = date('Y-m-d', strtotime('-7 days'));
                $end_date = date('Y-m-d', time());
                curl_setopt($ch, CURLOPT_URL, 'http://pub.alimama.com/report/getNewTbkRefundPaymentDetails.json?spm=a219t.7664554.1998457203.57.235935d9cTEOT6&refundType=1&searchType=1&DownloadID=DOWNLOAD_EXPORT_CPSPAYMENT_REFUND_OVERVIEW&startTime=' . $start_date . '&endTime=' . $end_date);
                curl_setopt($ch, CURLOPT_COOKIE, file_get_contents('/data/jianggo/tk_cookies.txt'));
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $ret = curl_exec($ch);
                curl_close($ch);
                file_put_contents('/data/jianggo/TaokeRefundDetail.xls', $ret);
                TbOrdersLogic::instance()->refundImport('/data/jianggo/TaokeRefundDetail.xls');
                break;
            case 'checkOrders':
                $time = strtotime(date('Y-m-d', time()));
                $orders = \app\common\model\TbOrdersItem::field('count(*) as count,trade_id,num_iid,pay_price')->where(['create_time' => ['between', [$time, $time + 86400]]])->group('trade_id,num_iid,pay_price')->having("count>1")->order('create_time desc')->select()->toArray();

                if ($orders) {
                    $trade_ids = array_column($orders, 'trade_id');
                    import('PHPExcel.PHPExcel', EXTEND_PATH);
                    $objPHPExcel = \PHPExcel_IOFactory::load('/data/jianggo/TaokeDayOrder' . date('Ymd') . '.xls');
                    $sheet = $objPHPExcel->getSheet(0);
                    $highestRow = $sheet->getHighestRow();
                    $highestColumm = $sheet->getHighestColumn();
                    $notice = array();
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $trade_id = (string) $sheet->getCell('Z' . $row)->getValue();
                        $num_iid = (string) $sheet->getCell('D' . $row)->getValue();
                        $pay_price = (string) $sheet->getCell('M' . $row)->getValue();
                        if (in_array($trade_id, $trade_ids)) {
                            $notice[$trade_id][$num_iid][$pay_price] = $notice[$trade_id][$num_iid][$pay_price] ? $notice[$trade_id][$num_iid][$pay_price] + 1 : 1;
                        }
                    }

                    foreach ($orders as $o) {
                        if ($o['count'] != $notice[$o['trade_id']][$o['num_iid']][(string) (float) $o['pay_price']]) {
                            $msg[] = $o['trade_id'];
                        }
                    }
                }
                $msg = $msg ? '重复订单' . implode(',', $msg) : '没有重复订单';
                $wechatObj = new \wechat\WechatApi();
                $wechatObj->send_wxmsg('oje9H1IBOYjneXgukOH8SJI7XB2U', 'text', $msg);
                break;
            case 'updateByDaBenYing':
//                TbOrdersLogic::instance()->updateByDaBenYing("2018-08-03 17:10:00");
                TbOrdersLogic::instance()->updateByDaBenYing(date("Y-m-d H:i:s", strtotime("-10 minutes")));
                TbOrdersLogic::instance()->updateByDaBenYing(date("Y-m-d H:i:s", strtotime("-10 minutes")),'settle_time');
                break;
        }
        $output->writeln("Successed");
    }

}
