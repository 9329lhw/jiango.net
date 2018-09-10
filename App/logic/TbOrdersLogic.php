<?php

namespace app\logic;

use think\Base;
use app\common\model\TbOrders;
use app\common\model\TbOrdersItem;
use app\common\model\UserPointLog;
use app\common\model\User;

class TbOrdersLogic extends Base {

    /**
     * 更新父订单信息
     * @author jiang
     */
    public function updateOrder($oid) {
        $num = 0;
        $order = TbOrders::where('oid', $oid)->find();
        $order_items = TbOrdersItem::where('oid', $order->oid)->select()->toArray();
        $tb_status_arr = array_column($order_items, 'tb_status');
        $updateflag = false;
        $notice = false;

        if ($order->uid && $order->status < 1) {
            $notice = true;
        }

        if ((in_array(3, $tb_status_arr) || in_array(14, $tb_status_arr))) {
            if ($order->status != 14) {
                $order->status = 14;
                if ($order->prize_status == 0) {
                    $order->prize_date = date('Y-m-d');
                    $order->prize_status = 99;
                }
                if ($order->jackpot_status == 0 && $order->jackpot_date = '0000-00-00') {
                    $order->jackpot_date = date('Y-m-d', strtotime('+1 day'));
                }
                $updateflag = true;
            }
        } elseif (in_array(12, $tb_status_arr)) {
            if ($order->status != 12) {
                $order->status = 12;
                if ($order->jackpot_status == 0) {
                    $order->jackpot_date = '0000-00-00';
                }
                if ($order->prize_status == 0) {
                    $order->prize_date = date('Y-m-d');
                    $order->prize_status = 99;
                }
//                if($order->prize_status == 99){
//                    $order->prize_date = '0000-00-00';
//                    $order->prize_status = 0;
//                }
                $updateflag = true;
            }
        } elseif (in_array(13, $tb_status_arr)) {
            if ($order->status != 13) {
                $order->status = 13;
                if ($order->jackpot_status == 0) {
                    $order->jackpot_date = '0000-00-00';
                }
                if ($order->prize_status == 99) {
                    $order->prize_date = '0000-00-00';
                    $order->prize_status = 0;
                } elseif ($order->prize_status == 1) {//中奖失效
                    $order->prize_status = 4;
                    $winners = db('winner')->alias('w')->field('w.*')->join('tb_orders o', 'o.oid=w.oid')->where(['w.oid' => $oid, 'o.prize_status' => 1, 'w.status' => 1])->select();
                    if ($winners) {
                        foreach ($winners as $w) {
                            $pointLog = new UserPointLog;
                            $pointLog->uid = $w['uid'];
                            $pointLog->point = -$w['point'];
                            $pointLog->type = "failure_prize";
                            $pointLog->about_id = $w['oid'];
                            if ($w['type'] == 'order') {
                                $pointLog->content = "订单失效直接获奖无效，订单号：" . $w['trade_id'];
                            } elseif ($w['type'] == 'second_agent') {
                                $pointLog->content = "下级用户订单失效获奖无效，订单号：" . $w['trade_id'];
                            } elseif ($w['type'] == 'first_agent') {
                                $pointLog->content = "下下级用户订单失效获奖无效，订单号：" . $w['trade_id'];
                            }
                            $pointLog->save();
                            User::find($w['uid'])->save(['point' => ['exp', 'point-' . $w['point']], 'total_point' => ['exp', 'total_point-' . $w['point']], 'enabled_point' => ['exp', 'enabled_point-' . $w['point']]]);
                        }
                        db('winner')->where(['oid' => $oid, 'status' => 1])->update(['status' => 0]);
                    }
                }
                $rebate = UserPointLog::where(['about_id' => $oid, 'type' => ['in', ['order_rebate', 'order_rebate_second', 'order_rebate_first']]])->select();
                foreach ($rebate as $r) {
                    $pointLog = new UserPointLog;
                    $pointLog->uid = $r['uid'];
                    $pointLog->point = -$r['point'];
                    $pointLog->type = "failure_" . $r['type'];
                    $pointLog->about_id = $oid;
                    $pointLog->content = "订单失效抽奖无效";
                    $pointLog->save();
                    User::find($r['uid'])->save(['point' => ['exp', 'point-' . $r['point']], 'total_point' => ['exp', 'total_point-' . $r['point']], 'enabled_point' => ['exp', 'enabled_point-' . $r['point']]]);
                }
                $updateflag = true;
            }
        }
        $total_pay_price = $total_settle_price = $total_forecast_income = $total_income = $total_commission_fee = $total_subsidy_fee = $jackpot_amount = 0;
        $earning_time = 0;
        foreach ($order_items as $oi) {
            $create_time = $oi['create_time'];
            $seller_shop_title = $oi['seller_shop_title'];
            $pid = $oi['pid'];
            if (in_array($oi['tb_status'], [3, 12, 14])) {
                $total_pay_price += $oi['pay_price'];
                $jackpot_amount += $oi['income'];
                $total_settle_price += $oi['settle_price'];
                $total_forecast_income += $oi['forecast_income'];
                $total_income += $oi['income'];
                $total_commission_fee += $oi['commission_fee'];
                $total_subsidy_fee += $oi['subsidy_fee'];
            }
            $num += $oi['item_num'];
            $earning_time = $oi['earning_time'] > $earning_time ? $oi['earning_time'] : $earning_time;
        }
        if ($updateflag || $order->total_pay_price != $total_pay_price || $order->total_settle_price != $total_settle_price || $order->total_forecast_income != $total_forecast_income || $order->total_income != $total_income || $order->total_commission_fee != $total_commission_fee || $order->total_subsidy_fee != $total_subsidy_fee || $order->earning_time != $earning_time) {
            $order->create_time = $create_time;
            $order->seller_shop_title = $seller_shop_title;
            $order->total_pay_price = $total_pay_price;
            $order->total_settle_price = $total_settle_price;
            $order->total_forecast_income = $total_forecast_income;
            $order->total_income = $total_income;
            $order->total_commission_fee = $total_commission_fee;
            $order->total_subsidy_fee = $total_subsidy_fee;
            if (empty($order->pid)) {
                $order->pid = $pid;
            }
            $order->num = $num;
            $order->earning_time = $earning_time;
            if ($order->jackpot_status == 0) {
                $order->jackpot_amount = $jackpot_amount;
            }
            $order->save();
            if (session('admin_id')) {
                model('AdminLog')->adminLogAdd("订单号{$order->trade_id}，状态{$this->tbStatusList[$order->status]}，奖池时间{$order->jackpot_date}，参奖时间{$order->prize_date}", 'update', 'order');
            }
        }
        if ($notice) {
            $wechatObj = new \wechat\WechatApi();
            $userExt = db('UserExtension')->where(['uid' => $order->uid, 'extension' => 'openid', 'key' => $wechatObj->getAppid()])->find();
            if ($userExt) {
                $data = [
                    'first' => ['value' => '您好，您有新的订单追踪成功！', 'color' => '#3a5fad'],
                    'keyword1' => ['value' => $order->seller_shop_title, 'color' => '#3a5fad'],
                    'keyword2' => ['value' => $order_items[0]['item_title'], 'color' => '#3a5fad'],
                    'keyword3' => ['value' => date('Y-m-d H:i:s', $order->create_time), 'color' => '#3a5fad'],
                    'keyword4' => ['value' => '￥' . $order->total_pay_price, 'color' => '#3a5fad'],
                    'keyword5' => ['value' => in_array($order->status, [3, 12, 14]) ? '已付款' : '未付款', 'color' => '#3a5fad'],
                    'remark' => ['value' => in_array($order->status, [3, 12, 14]) ? '此订单可参与红包抽奖，点击前往' : '更多详情请下载APP', 'color' => '#ff0000']
                ];
                $wechatObj->sendTemplateMsg($userExt['val'], '4LyuVjwjtteLOjNUQPMGaH82FTDIHUvFlYIBM6-8PC4', $data, SERVER_PATH . (in_array($order->status, [3, 12, 14]) ? "/lotteryDraw" : "/orders?type=all"));
            }
        }
    }

    /**
     * 领取订单
     * @author jiang
     */
    public function ledOrder($trade_id, $uid) {
        if (empty($trade_id)) {
            return '订单号不能为空';
        }
        if (empty($uid)) {
            return '用户ID不能为空';
        }
        if (!preg_match('/^[0-9]+$/', $trade_id)) {
            return '订单号格式错误，需为数字';
        }
        $order = TbOrders::get(['trade_id' => $trade_id]);
        if ($order) {
            if ($order->uid == 0) {
                $order->uid = $uid;
                $order->led_time = time();
                $result = $order->save();
                $tbID = \app\common\model\UserExtension::where(['uid' => $uid, 'extension' => 'tbID'])->find();
                if (!$tbID) {
                    $extension = new \app\common\model\UserExtension;
                    $extension->uid = $uid;
                    $extension->extension = 'tbID';
                    $extension->val = substr($trade_id, -6);
                    $extension->save();
                }
                if ($result) {
                    db("UserLogs")->insert(['uid' => $uid, 'type' => 'led_order', 'msg' => '用户认领订单' . $trade_id, 'create_time' => time()]);
                    return true;
                } else {
                    return '提交失败';
                }
            } else {
                if ($order->uid == $uid) {
                    return '您已经提交过该订单，请勿重复提交';
                } else {
                    if ($order->prize_status != 1) {
                        $log = db('UserLogs')->where(['uid' => $order->uid, 'type' => 'led_order', 'msg' => ['like', "%$trade_id%"]])->find();
                        if (!$log) {
                            $order->uid = $uid;
                            $result = $order->save();
                            if ($order->status > 0) {
                                $tbID = \app\common\model\UserExtension::where(['uid' => $uid, 'extension' => 'tbID'])->find();
                                if (!$tbID) {
                                    $extension = new \app\common\model\UserExtension;
                                    $extension->uid = $uid;
                                    $extension->extension = 'tbID';
                                    $extension->val = substr($trade_id, -6);
                                    $extension->save();
                                }
                            }
                            if ($result) {
                                db("UserLogs")->insert(['uid' => $uid, 'type' => 'led_order', 'msg' => '用户认领订单' . $trade_id, 'create_time' => time()]);
                                return true;
                            } else {
                                return '提交失败';
                            }
                        } else {
                            return '该订单已被领取';
                        }
                    } else {
                        return '该订单已被领取';
                    }
                }
            }
        } else {
            $order = new TbOrders;
            $order->trade_id = $trade_id;
            $order->uid = $uid;
            $order->led_time = time();
            $result = $order->save();
            if ($result) {
                db("UserLogs")->insert(['uid' => $uid, 'type' => 'led_order', 'msg' => '用户认领订单' . $trade_id, 'create_time' => time()]);
                $wechat = new \wechat\WechatApi();
                $result = $wechat->send_wxmsg('oje9H1GovO7EzJad6uBeISaijmI4', 'text', '有新的订单等待追踪，请尽快更新');
                return true;
            } else {
                return '提交失败';
            }
        }
    }

    /**
     * 删除订单
     * @author jiang
     */
    public function deleteOrder($trade_id, $uid) {
        if (empty($trade_id)) {
            return '订单号不能为空';
        }
        if (empty($uid)) {
            return '用户ID不能为空';
        }
        $order = TbOrders::get(['trade_id' => $trade_id]);
        if ($order) {
            if ($order->uid != $uid) {
                return '无该订单权限';
            }
            if ($order->status != 0 && $order->status != -1) {
                return '无法删除';
            }
            $order->delete();
            return true;
        } else {
            return '订单不存在';
        }
    }

    /**
     * 好友订单列表
     * @author jiang
     */
    public function lists($where = array(), $page = 0, $pageSize = 10) {
        $list = TbOrders::lists($where, $page, $pageSize);
        $data = array('list' => [], 'count' => 0, 'total_page' => 0);
        foreach ($list['list'] as $k => $o) {
            $data['list'][$k]['trade_id'] = $o['trade_id'];
            $data['list'][$k]['total_pay_price'] = $o['total_pay_price'];
            $data['list'][$k]['status'] = $o['status'];
            $data['list'][$k]['status_label'] = TbOrders::$tbStatusList[$o['status']];
            $data['list'][$k]['prize_status'] = $o['prize_status'];
            $data['list'][$k]['prize_status_label'] = $o['status'] > 0 ? ($o['prize_status'] == 3 ? '未中奖' : TbOrders::$prizeStatusList[$o['prize_status']]) : '';
            $data['list'][$k]['create_time'] = date('Y-m-d H:i', $o['create_time'] == 0 ? $o['led_time'] : $o['create_time']);
            $data['list'][$k]['item'] = array();
            if ($o['status']) {
                $data['list'][$k]['item'] = TbOrdersItem::where('oid', $o['oid'])->field('item_title,item_num,pay_price,pic_url')->select();
            }
        }
        $data['count'] = $list['count'];
        $data['total_page'] = $page ? ceil($data['count'] / $pageSize) : 1;
        return $data;
    }

    /**
     * 好友订单列表
     * @author jiang
     */
    public function agentOrders($uid, $where = array(), $page = 0, $pageSize = 10) {
        $list = TbOrders::agentOrders($uid, $where, $page, $pageSize);
        $data = array('list' => [], 'count' => 0, 'total_page' => 0);
        foreach ($list['list'] as $k => $o) {
            $data['list'][$k]['trade_id'] = preg_replace('/^(\d{5})\d*(\d{2})$/', '$1******$2', $o['trade_id']);
            $data['list'][$k]['total_pay_price'] = $o['total_pay_price'];
            $data['list'][$k]['status'] = $o['status'];
            $data['list'][$k]['status_label'] = TbOrders::$tbStatusList[$o['status']];
            $data['list'][$k]['prize_status'] = $o['prize_status'];
            $data['list'][$k]['prize_status_label'] = $o['status'] > 0 ? ($o['prize_status'] == 3 ? '未中奖' : TbOrders::$prizeStatusList[$o['prize_status']]) : '';
            $data['list'][$k]['create_time'] = date('Y-m-d H:i', $o['create_time'] == 0 ? $o['led_time'] : $o['create_time']);
            $data['list'][$k]['item'] = array();
            if ($o['status']) {
                $data['list'][$k]['item'] = TbOrdersItem::where('oid', $o['oid'])->field('item_title,item_num,pay_price,pic_url')->select();
            }
        }
        $data['count'] = $list['count'];
        $data['total_page'] = $page ? ceil($data['count'] / $pageSize) : 1;
        return $data;
    }

    /**
     * 代言人订单列表
     * @author jiang
     */
    public function bossOrders($uid, $where = array(), $page = 0, $pageSize = 10) {
        $list = TbOrders::bossOrders($uid, $where, $page, $pageSize);
        $data = array('list' => [], 'count' => 0, 'total_page' => 0);
        foreach ($list['list'] as $k => $o) {
            $data['list'][$k]['trade_id'] = preg_replace('/^(\d{5})\d*(\d{2})$/', '$1******$2', $o['trade_id']);
            $data['list'][$k]['total_pay_price'] = $o['total_pay_price'];
            $data['list'][$k]['status'] = $o['status'];
            $data['list'][$k]['commission'] = floor($o['total_forecast_income'] * $o['boss_agent_rate']);
            $data['list'][$k]['complete'] = floatval($o['boss_agent_commission']) ? 1 : 0;
            $data['list'][$k]['status_label'] = TbOrders::$tbStatusList[$o['status']];
            $data['list'][$k]['create_time'] = date('Y-m-d', $o['create_time'] == 0 ? $o['led_time'] : $o['create_time']);
            $data['list'][$k]['item'] = array();
            if ($o['status']) {
                $data['list'][$k]['item'] = TbOrdersItem::where('oid', $o['oid'])->field('item_title,item_num,pay_price,pic_url')->select();
            }
        }
        $data['count'] = $list['count'];
        $data['total_page'] = $page ? ceil($data['count'] / $pageSize) : 1;
        return $data;
    }

    /**
     * 合伙人订单列表
     * @author jiang
     */
    public function partnerOrders($uid, $where = array(), $page = 0, $pageSize = 10) {
        $list = TbOrders::partnerOrders($uid, $where, $page, $pageSize);
        $data = array('list' => [], 'count' => 0, 'total_page' => 0);
        foreach ($list['list'] as $k => $o) {
            $data['list'][$k]['trade_id'] = preg_replace('/^(\d{5})\d*(\d{2})$/', '$1******$2', $o['trade_id']);
            $data['list'][$k]['total_pay_price'] = $o['total_pay_price'];
            $data['list'][$k]['status'] = $o['status'];
            $data['list'][$k]['commission'] = 0;
            $data['list'][$k]['complete'] = 0;
            if ($o['partner_agent_uid'] == $uid) {
                $data['list'][$k]['commission'] += floor($o['total_forecast_income'] * $o['partner_agent_rate']);
                $data['list'][$k]['complete'] = floatval($o['partner_agent_commission']) ? 1 : 0;
            }
            if ($o['boss_agent_uid'] == $uid) {
                $data['list'][$k]['commission'] += floor($o['total_forecast_income'] * $o['boss_agent_rate']);
                $data['list'][$k]['complete'] = floatval($o['boss_agent_commission']) ? 1 : 0;
            }
            $data['list'][$k]['status_label'] = TbOrders::$tbStatusList[$o['status']];
            $data['list'][$k]['create_time'] = date('Y-m-d', $o['create_time'] == 0 ? $o['led_time'] : $o['create_time']);
            $data['list'][$k]['item'] = array();
            if ($o['status']) {
                $data['list'][$k]['item'] = TbOrdersItem::where('oid', $o['oid'])->field('item_title,item_num,pay_price,pic_url')->select();
            }
        }
        $data['count'] = $list['count'];
        $data['total_page'] = $page ? ceil($data['count'] / $pageSize) : 1;
        return $data;
    }

    /**
     * 订单导入
     * @author jiang
     */
    public function import($file = '',$setUnJianggo = true) {
        if (!$file) {
            return ['success' => 0, 'insert' => 0, 'update' => 0, 'fail' => []];
        }
        import('PHPExcel.PHPExcel', EXTEND_PATH);
        $objPHPExcel = \PHPExcel_IOFactory::load($file);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumm = $sheet->getHighestColumn();
        $orderObj = model('TbOrders');
        $orderItemObj = model('TbOrdersItem');

        $success = $insert = $update = 0;
        $fail = array();
        $oid = $item_id = 0; //用于处理同一订单同一商品同一商品数
        $updateMain = false; //是否更新主订单
        if($sheet->getCell('A1')->getValue() != '创建时间' || $sheet->getCell('D1')->getValue() != '商品ID' || $sheet->getCell('F1')->getValue() != '所属店铺'
                || $sheet->getCell('G1')->getValue() != '商品数' || $sheet->getCell('I1')->getValue() != '订单状态' || $sheet->getCell('K1')->getValue() != '收入比率'
                || $sheet->getCell('M1')->getValue() != '付款金额' || $sheet->getCell('N1')->getValue() != '效果预估' || $sheet->getCell('O1')->getValue() != '结算金额'
                 || $sheet->getCell('P1')->getValue() != '预估收入'  || $sheet->getCell('R1')->getValue() != '佣金比率'  || $sheet->getCell('S1')->getValue() != '佣金金额'
                 || ($sheet->getCell('T1')->getValue() == '技术服务费比率' && ($sheet->getCell('Z1')->getValue() != '订单编号' ||  $sheet->getCell('AD1')->getValue() != '广告位ID'))
                 || ($sheet->getCell('T1')->getValue() != '技术服务费比率' && ($sheet->getCell('Y1')->getValue() != '订单编号' ||  $sheet->getCell('AC1')->getValue() != '广告位ID'))){
            return ['success' => 0, 'insert' => 0, 'update' => 0, 'fail' => ['文件格式错误']];
        }
        for ($row = 2; $row <= $highestRow; $row++) {
            if($sheet->getCell('T1')->getValue() == '技术服务费比率'){
                $trade_id = $sheet->getCell('Z' . $row)->getValue();
                $pid = 'mm_130792044_' . $sheet->getCell('AB' . $row)->getValue() . '_' . $sheet->getCell('AD' . $row)->getValue();
                $subsidy_rate = $sheet->getCell('U' . $row)->getValue();
                $subsidy_rate = gettype($subsidy_rate) == 'double' ? $subsidy_rate * 100 : floatval($subsidy_rate);
                $subsidy_fee = $sheet->getCell('V' . $row)->getValue();
            }else{
                $trade_id = $sheet->getCell('Y' . $row)->getValue();
                $pid = 'mm_130792044_' . $sheet->getCell('AA' . $row)->getValue() . '_' . $sheet->getCell('AC' . $row)->getValue();
                $subsidy_rate = $sheet->getCell('T' . $row)->getValue();
                $subsidy_rate = gettype($subsidy_rate) == 'double' ? $subsidy_rate * 100 : floatval($subsidy_rate);
                $subsidy_fee = $sheet->getCell('U' . $row)->getValue();
            }
            
            $num_iid = $sheet->getCell('D' . $row)->getValue();
            $seller_shop_title = $sheet->getCell('F' . $row)->getValue();
            $item_num = $sheet->getCell('G' . $row)->getValue();
            $pay_price = $sheet->getCell('M' . $row)->getValue();
            $tb_status = array_search($sheet->getCell('I' . $row)->getValue(), TbOrders::$tbStatusList);
            if (!$tb_status) {
                $fail[] = $trade_id . '状态错误' . PHP_EOL;
                continue;
            }
            $key = "{$num_iid}_{$item_num}";
            $settle_price = $sheet->getCell('O' . $row)->getValue();
            $forecast_income = $sheet->getCell('N' . $row)->getValue();
            $income_rate = $sheet->getCell('K' . $row)->getValue();
            $income_rate = gettype($income_rate) == 'double' ? $income_rate * 100 : floatval($income_rate);
            $income = $sheet->getCell('P' . $row)->getValue();
            $commission_rate = $sheet->getCell('R' . $row)->getValue();
            $commission_rate = gettype($commission_rate) == 'double' ? $commission_rate * 100 : floatval($commission_rate);
            $commission_fee = $sheet->getCell('S' . $row)->getValue();
            $divided_rate = $sheet->getCell('L' . $row)->getValue();
            $divided_rate = gettype($divided_rate) == 'double' ? $divided_rate * 100 : floatval($divided_rate);
            $create_time = $sheet->getCell('A' . $row)->getValue();
            $earning_time = $sheet->getCell('Q' . $row)->getValue();
            $order = $orderObj->where('trade_id', $trade_id)->find();
            $tbID = substr($trade_id, -6);
            if (!$order) {
                $order = new TbOrders;
                $order->trade_id = $trade_id;
                $user = \app\common\model\UserExtension::where(['extension' => 'tbID', 'val' => $tbID])->select()->toArray();
                if ($user) {
                    $matching_uid = 0;
                    if (count($user) == 1) {
                        $matching_uid = $user[0]['uid'];
                    } else {
                        $pids = \app\common\model\UserRelation::alias('ur')->where(['ur.uid' => ['in', array_column($user, 'uid')]])->join('user u', 'u.uid=ur.boss_agent_uid')->column('pid', 'ur.uid');
                        $matching_uid = 0;
                        foreach ($user as $u) {
                            if (!$pids[$u['uid']]) {
                                $pids[$u['uid']] = SHOP_PID;
                            }
                        }
                        foreach ($pids as $k => $p) {
                            if ($p == $pid) {
                                if ($matching_uid) {
                                    $matching_uid = 0;
                                    break;
                                } else {
                                    $matching_uid = $k;
                                }
                            }
                        }
                    }
                    $order->uid = $matching_uid;
                }
                $order->save();
                if (session('admin_id')) {
                    model('AdminLog')->adminLogAdd("订单号{$order->trade_id}", 'add', 'order');
                }
                $order = $orderObj::find($order->oid);
            } elseif ($order->uid && $order->status < 1) {
                $user = \app\common\model\UserExtension::where(['extension' => 'tbID', 'uid' => $order->uid])->find();
                if (!$user) {
                    $extension = new \app\common\model\UserExtension;
                    $extension->uid = $order->uid;
                    $extension->extension = 'tbID';
                    $extension->val = $tbID;
                    $extension->save();
                }
            }
            if ($oid != $order->oid) {
                if ($oid && $updateMain) {
                    $this->updateOrder($oid);
                }
                $oid = $order->oid;
                $item_id = 0;
                $updateMain = false;
            }

            //必须不按状态导入
            $where = ['trade_id' => $trade_id, 'num_iid' => $num_iid, 'item_num' => $item_num];
            if ($item_id) {
                $where['id'] = ['gt', $item_id];
            }
            $order_item = $orderItemObj->where($where)->order('id')->find();
            if (!$order_item) {
                $updateMain = true;
                $order_item = new TbOrdersItem;
                $order_item->oid = $order->oid;
                $order_item->trade_id = $trade_id;
                $order_item->num_iid = $num_iid;
                $order_item->seller_shop_title = $seller_shop_title;
                $order_item->item_title = $sheet->getCell('C' . $row)->getValue();
                $order_item->item_num = $item_num;
                $order_item->tb_status = $tb_status;
                $order_item->pay_price = $pay_price;
                $order_item->settle_price = $settle_price;
                $order_item->forecast_income = $forecast_income;
                $order_item->income_rate = $income_rate;
                $order_item->income = $income;
                $order_item->commission_rate = $commission_rate;
                $order_item->commission_fee = $commission_fee;
                $order_item->subsidy_rate = $subsidy_rate;
                $order_item->subsidy_fee = $subsidy_fee;
                $order_item->divided_rate = $divided_rate;
                $order_item->earning_time = strtotime($earning_time);
                $order_item->create_time = strtotime($create_time);
                $order_item->pid = $pid;
                //走crontab定时器
//                $pic_url = db('goods')->where('num_iid',$num_iid)->value('pic_url');
//                if(!$pic_url){
//                    $req = new \TbkItemInfoGetRequest;
//                    $req->setFields("num_iid,title,pict_url");
//                    $req->setNumIids($num_iid);
//                    ob_start();
//                    $resp = $c->execute($req);
//                    ob_clean();
//                    $resp = json_decode(json_encode($resp),true);
//                    $pic_url = $resp['results']['n_tbk_item']['pict_url'];
//                }
                $pic_url = db('tb_goods')->where('num_iid', $num_iid)->value('pic_url');
                $order_item->pic_url = $pic_url ? $pic_url : '';
                $order_item->save();
                $insert++;
                if (session('admin_id')) {
                    model('AdminLog')->adminLogAdd("ID{$order_item->id}，订单号{$order_item->trade_id}，商品名{$order_item->item_title}，状态{TbOrders::$tbStatusList[$tb_status]}", 'add', 'orderItem');
                }
            } elseif ($order_item->tb_status != $tb_status || $order_item->pay_price != $pay_price || $order_item->settle_price != $settle_price || $order_item->forecast_income != $forecast_income || $order_item->income != $income || $order_item->commission_fee != $commission_fee || $order_item->subsidy_rate != $subsidy_rate || $order_item->subsidy_fee != $subsidy_fee || $order_item->earning_time != strtotime($earning_time)) {
                if ($order_item->tb_status != 13) {
                    $order_item->tb_status = $tb_status;
                    $order_item->pay_price = $pay_price;
                    $order_item->settle_price = $settle_price;
                    $order_item->forecast_income = $forecast_income;
                    $order_item->income_rate = $income_rate;
                    $order_item->income = $income;
                    $order_item->commission_rate = $commission_rate;
                    $order_item->commission_fee = $commission_fee;
                    $order_item->subsidy_rate = $subsidy_rate;
                    $order_item->subsidy_fee = $subsidy_fee;
                    $order_item->divided_rate = $divided_rate;
                    $order_item->earning_time = strtotime($earning_time);
                    $order_item->save();
                    $update++;
                    $updateMain = true;
                    if (session('admin_id')) {
                        model('AdminLog')->adminLogAdd("ID{$order_item->id}，订单号{$order_item->trade_id}，商品名{$order_item->item_title}，状态{TbOrders::$tbStatusList[$tb_status]}", 'update', 'orderItem');
                    }
                }
            }
            $item_id = $order_item->id;
            $success++;
        }
        if ($oid) {
            $this->updateOrder($oid);
        }
        if($setUnJianggo){
            $wechatObj = new \wechat\WechatApi();
            $un_orders = model('TbOrders')->alias('o')->field('o.trade_id,ue.val')->join('user_extension ue', 'ue.uid=o.uid')->where(['status' => 0, 'extension' => 'openid', 'key' => $wechatObj->getAppid()])->select()->toArray();
            model('TbOrders')->alias('o')->where(['status' => 0])->update(['status' => -1]);
            foreach ($un_orders as $o) {
                $wechatObj->send_wxmsg($o['val'], 'text', '订单号' . $o['trade_id'] . '未经奖购下单，如有疑问请联系客服：zhaoquan618（微信号）');
            }
        }
        return ['success' => $success, 'insert' => $insert, 'update' => $update, 'fail' => $fail];
    }

    /**
     * 维权订单导入
     * @author jiang
     */
    public function refundImport($file = '') {
        if (!$file) {
            return ['success' => 0, 'insert' => 0, 'update' => 0, 'fail' => []];
        }
        import('PHPExcel.PHPExcel', EXTEND_PATH);
        $objPHPExcel = \PHPExcel_IOFactory::load($file);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumm = $sheet->getHighestColumn();
        $orderObj = model('TbOrders');
        $orderItemObj = model('TbOrdersItem');
        $orderRefundObj = model('TbOrdersRefund');
        if ($sheet->getCell('A1')->getValue() != '淘宝订单编号') {
            return ['success' => 0, 'insert' => 0, 'update' => 0, 'fail' => '文件格式错误'];
        }
        $success = 0;
        for ($row = 2; $row <= $highestRow; $row++) {
            $trade_id = $sheet->getCell('A' . $row)->getValue();
            $item_title = $sheet->getCell('C' . $row)->getValue();
            $refund_amount = $sheet->getCell('D' . $row)->getValue();
            $refund_income = $sheet->getCell('E' . $row)->getValue();
            $refund_status = $sheet->getCell('F' . $row)->getValue();
            if ($refund_status != '维权成功') {
                continue;
            }
            $create_time = $sheet->getCell('H' . $row)->getValue();
            $create_time = strtotime($create_time);
            $complete_time = $sheet->getCell('I' . $row)->getValue();
            $complete_time = strtotime($complete_time);
            $refund = $orderRefundObj->where(['trade_id' => $trade_id, 'item_title' => $item_title])->find();

            if (!$refund) {
                $order = $orderObj->where('trade_id', $trade_id)->find();
                if (!$order) {
                    continue;
                }
                $refund = new \app\common\model\TbOrdersRefund;
                $refund->trade_id = $trade_id;
                $refund->oid = $order->oid;
                $refund->item_title = $item_title;
                $refund->refund_amount = $refund_amount;
                $refund->refund_income = $refund_income;
                $refund->create_time = $create_time;
                $refund->complete_time = $complete_time;
                $orderItem = $orderItemObj->where(['trade_id' => $trade_id, 'item_title' => $item_title])->find();
                if ($orderItem) {
                    $refund->num_iid = $orderItem->num_iid;
                    if ($orderItem->tb_status != 13 && $orderItem->pay_price == $refund_amount) {
                        $orderItem->tb_status = 13;
                        $orderItem->pay_price = 0;
                        $orderItem->settle_price = 0;
                        $orderItem->forecast_income = 0;
                        $orderItem->income = 0;
                        $orderItem->commission_fee = 0;
                        $orderItem->subsidy_fee = 0;
                        $orderItem->save();
                        $this->updateOrder($order->oid);
                    }
                }
                $refund->save();
                $success++;
            }
        }
        return ['success' => $success];
    }

    /**
     * 代言人订单归属结算
     * @author jiang
     */
    public function bossSettle() {
        TbOrders::alias('o')->join('user u', 'u.pid=o.pid')
                ->where(['u.boss_agent' => ['gt', 0], 'o.boss_agent_uid' => 0, 'o.create_time' => ['exp', '>u.boss_agent_time']])
                ->update(['o.boss_agent_uid' => ['exp', 'u.uid'], 'boss_agent_rate' => \lib\cache\CacheTool::configsCache('boss_agent_order_commission_rate')]);
        TbOrders::where(['boss_agent_uid' => ['gt', 0], 'boss_agent_commission' => ['exp', "<>floor(total_income*boss_agent_rate)"]])
                ->update(['boss_agent_commission' => ['exp', "floor(total_income*boss_agent_rate)"]]);
    }

    /**
     * 合伙人订单归属结算
     * @author jiang
     */
    public function partnerSettle() {
        //已归属下级代言人的订单
        TbOrders::alias('o')->join('user_relation ur', 'ur.uid=o.boss_agent_uid')->join('user u', 'u.uid=ur.partner_agent_uid')
                ->where(['o.boss_agent_uid' => ['gt', 0], 'u.partner_agent' => ['gt', 0], 'o.partner_agent_uid' => 0, 'o.create_time' => ['exp', '>u.partner_agent_time']])
                ->update(['o.partner_agent_uid' => ['exp', 'u.uid'], 'partner_agent_rate' => \lib\cache\CacheTool::configsCache('partner_agent_order_commission_rate')]);
        //本身既是合伙人又是代言人的
        TbOrders::alias('o')->join('user u', 'u.uid=o.boss_agent_uid')
                ->where(['o.boss_agent_uid' => ['gt', 0], 'u.partner_agent' => ['gt', 0], 'o.partner_agent_uid' => 0, 'o.create_time' => ['exp', '>u.partner_agent_time']])
                ->update(['o.partner_agent_uid' => ['exp', 'u.uid'], 'partner_agent_rate' => \lib\cache\CacheTool::configsCache('partner_agent_order_commission_rate')]);
        TbOrders::alias('o')->join('user_relation ur', 'ur.uid=o.uid')->join('user u', 'u.uid=ur.partner_agent_uid')
                ->where(['u.partner_agent' => ['gt', 0], 'o.partner_agent_uid' => 0, 'o.create_time' => ['exp', '>u.partner_agent_time']])
                ->update(['o.partner_agent_uid' => ['exp', 'u.uid'], 'partner_agent_rate' => \lib\cache\CacheTool::configsCache('partner_agent_order_commission_rate')]);
        TbOrders::where(['partner_agent_uid' => ['gt', 0], 'partner_agent_commission' => ['exp', "<>floor(total_income*partner_agent_rate)"]])
                ->update(['partner_agent_commission' => ['exp', "floor(total_income*partner_agent_rate)"]]);
    }

    /**
     * 抽奖信息
     */
    public function rebateIndex($uid) {
        $time = strtotime('-7 days');
        $time = $time > 1526832000 ? $time : 1526832000;
        $where['ur.uid|ur.first_agent_uid|ur.second_agent_uid'] = $uid;
        $where['o.uid'] = ['gt', 0];
        $where['status'] = ['in', [3, 12, 14]];
        $where['create_time'] = ['gt', $time];
        $where['oe.id'] = null;
        $count = TbOrders::alias('o')->where($where)->join('user_relation ur', 'ur.uid=o.uid')
                ->join('tb_order_extension oe', "oe.oid=o.oid and ((oe.extension='order_rebate' and ur.uid=$uid) or (oe.extension='order_rebate_first' and ur.first_agent_uid=$uid) or (oe.extension='order_rebate_second' and ur.second_agent_uid=$uid))", 'left')
                ->count();
        $redpacks = [];
        if ($count) {
            $rebate_order = TbOrders::alias('o')->field('ur.uid,ur.first_agent_uid,ur.second_agent_uid,o.total_forecast_income,o.trade_id,o.oid')->where($where)->join('user_relation ur', 'ur.uid=o.uid')
                            ->join('tb_order_extension oe', "oe.oid=o.oid and ((oe.extension='order_rebate' and ur.uid=$uid) or (oe.extension='order_rebate_first' and ur.first_agent_uid=$uid) or (oe.extension='order_rebate_second' and ur.second_agent_uid=$uid))", 'left')
                            ->order('o.create_time')->find();
            if ($rebate_order['uid'] == $uid) {
                $redpack = $rebate_order['total_forecast_income'] * 0.35;
            } elseif ($rebate_order['second_agent_uid'] == $uid) {
                $redpack = $rebate_order['total_forecast_income'] * 0.06;
            } elseif ($rebate_order['first_agent_uid'] == $uid) {
                $redpack = $rebate_order['total_forecast_income'] * 0.04;
            }
            if ($redpack < 0.01) {
                $redpack = 0.01;
            } else {
                $redpack = round($redpack, 2);
            }
            $redpacks = [$redpack];
            $rand = mt_rand(3, 5);
            for ($i = 0; $i < $rand; $i++) {
                $money = mt_rand(0, $redpack * 100) / 100;
                $redpacks[] = $money > 0 ? $money : 0.01;
            }
            for ($i = $rand; $i < 7; $i++) {
                $money = mt_rand($redpack * 100, ($rebate_order['total_forecast_income'] > 0.1 ? $rebate_order['total_forecast_income'] : 0.1) * 100) / 100;
                $redpacks[] = $money > 0 ? $money : 0.01;
            }
//            for ($i = 0; $i < 7; $i++) {
//                $money = mt_rand(1, ($rebate_order['total_forecast_income'] > 0.1 ? $rebate_order['total_forecast_income'] : 0.1) * 100) / 100;
//                $redpacks[] = $money > 0 ? $money : 0.01;
//            }
            shuffle($redpacks);
        } else {
            for ($i = 0; $i < 8; $i++) {
                $money = mt_rand(0, 10000) / 100;
                $redpacks[] = $money;
            }
        }
        $data['count'] = $count;
        $data['redpacks'] = $redpacks;
        return $data;
    }

    /**
     * 抽奖
     */
    public function rebate($uid) {
        $time = strtotime('-7 days');
        $time = $time > 1526832000 ? $time : 1526832000;
        $where['ur.uid|ur.first_agent_uid|ur.second_agent_uid'] = $uid;
        $where['o.uid'] = ['gt', 0];
        $where['status'] = ['in', [3, 12, 14]];
        $where['create_time'] = ['gt', $time];
        $where['oe.id'] = null;
        $rebate_order = TbOrders::alias('o')->field('ur.uid,ur.first_agent_uid,ur.second_agent_uid,o.total_forecast_income,o.trade_id,o.oid')->where($where)->join('user_relation ur', 'ur.uid=o.uid')
                        ->join('tb_order_extension oe', "oe.oid=o.oid and ((oe.extension='order_rebate' and ur.uid=$uid) or (oe.extension='order_rebate_first' and ur.first_agent_uid=$uid) or (oe.extension='order_rebate_second' and ur.second_agent_uid=$uid))", 'left')
                        ->order('o.create_time')->find();
        if (!$rebate_order) {
            return "您暂无抽奖机会！";
        }
        $pointLog = new UserPointLog;
        $pointLog->uid = $uid;
        $pointLog->content = "订单抽奖红包";
        if ($rebate_order['uid'] == $uid) {
            $redpack = $rebate_order['total_forecast_income'] * 0.35;
            $pointLog->type = "order_rebate";
        } elseif ($rebate_order['second_agent_uid'] == $uid) {
            $redpack = $rebate_order['total_forecast_income'] * 0.06;
            $pointLog->type = "order_rebate_second";
        } elseif ($rebate_order['first_agent_uid'] == $uid) {
            $redpack = $rebate_order['total_forecast_income'] * 0.04;
            $pointLog->type = "order_rebate_first";
        }
        if ($redpack < 0.01) {
            $redpack = 0.01;
        } else {
            $redpack = round($redpack, 2);
        }
        $pointLog->point = $redpack * 100;
        $pointLog->about_id = $rebate_order['oid'];
        $pointLog->save();
        \app\common\model\User::find($uid)->save(['point' => ['exp', 'point+' . $pointLog->point], 'total_point' => ['exp', 'total_point+' . $pointLog->point]]);
        db('tb_order_extension')->insert(['oid' => $rebate_order['oid'], 'extension' => $pointLog->type, 'val' => $redpack]);
        $data = $this->rebateIndex($uid);
        $data['redpack'] = $redpack;
        return $data;
    }
    
    /*
     * 更新自大本营
     */
    public function updateByDaBenYing($date,$orderQueryType = ''){
        $session = cache('tb_session');
        $param['startTime'] = $date;
        $param['pageSize'] = 100;
        $param['appkey'] = '1311360650099917';
        $param['appsecret'] = '02008dbcb791c23778e0226577451335';
        $param['session'] = $session;
        if($orderQueryType){
            $param['orderQueryType'] = $orderQueryType;
        }
        $page = 1;
        $oid = $item_id = 0; //用于处理同一订单同一商品同一商品数
        $orderObj = model('TbOrders');
        $orderItemObj = model('TbOrdersItem');
        while ($page) {
            $param['pageNo'] = $page;
            $result = file_get_contents("http://www.jxb001.cn/openApi/order/api?" . http_build_query($param));
            $result = json_decode($result, true);
            if (!$result['code']) {
                $setUnJianggo = !$orderQueryType?true:false;
                if ($result['data']) {
                    foreach ($result['data'] as $v) {
                        $trade_id = $v['trade_parent_id'];
                        $pid = 'mm_130792044_' . $v['site_id'] . '_' . $v['adzone_id'];
                        $subsidy_rate = floatval($v['subsidy_rate'])*100;
                        $subsidy_fee = $v['subsidy_fee'];

                        $num_iid = $v['num_iid'];
                        $seller_shop_title = $v['seller_shop_title'];
                        $item_num = $v['item_num'];
                        $pay_price = $v['alipay_total_price'];
                        $tb_status = $v['tk_status'];
                        $key = "{$num_iid}_{$item_num}";
                        $settle_price = $v['pay_price'];
                        $forecast_income = $v['pub_share_pre_fee'];
                        $income_rate = $v['income_rate']*100;
                        $income = $v['commission'];
                        $commission_rate = $v['total_commission_rate']*100;
                        $commission_fee = $v['total_commission_fee'];
                        $divided_rate = $v['commission_rate']*100;
                        $create_time = $v['create_time'];
                        $earning_time = $v['earning_time'];
                        
                        $order = $orderObj->where('trade_id', $trade_id)->find();
                        $tbID = substr($trade_id, -6);
                        if (!$order) {
                            $order = new TbOrders;
                            $order->trade_id = $trade_id;
                            $user = \app\common\model\UserExtension::where(['extension' => 'tbID', 'val' => $tbID])->select()->toArray();
                            if ($user) {
                                $matching_uid = 0;
                                if (count($user) == 1) {
                                    $matching_uid = $user[0]['uid'];
                                } else {
                                    $pids = \app\common\model\UserRelation::alias('ur')->where(['ur.uid' => ['in', array_column($user, 'uid')]])->join('user u', 'u.uid=ur.boss_agent_uid')->column('pid', 'ur.uid');
                                    $matching_uid = 0;
                                    foreach ($user as $u) {
                                        if (!$pids[$u['uid']]) {
                                            $pids[$u['uid']] = SHOP_PID;
                                        }
                                    }
                                    foreach ($pids as $k => $p) {
                                        if ($p == $pid) {
                                            if ($matching_uid) {
                                                $matching_uid = 0;
                                                break;
                                            } else {
                                                $matching_uid = $k;
                                            }
                                        }
                                    }
                                }
                                $order->uid = $matching_uid;
                            }
                            $order->save();
                            if (session('admin_id')) {
                                model('AdminLog')->adminLogAdd("订单号{$order->trade_id}", 'add', 'order');
                            }
                            $order = $orderObj::find($order->oid);
                        } elseif ($order->uid && $order->status < 1) {
                            $user = \app\common\model\UserExtension::where(['extension' => 'tbID', 'uid' => $order->uid])->find();
                            if (!$user) {
                                $extension = new \app\common\model\UserExtension;
                                $extension->uid = $order->uid;
                                $extension->extension = 'tbID';
                                $extension->val = $tbID;
                                $extension->save();
                            }
                        }
                        if ($oid != $order->oid) {
                            if ($oid && $updateMain) {
                                $this->updateOrder($oid);
                            }
                            $oid = $order->oid;
                            $item_id = 0;
                            $updateMain = false;
                        }

                        //必须不按状态导入
                        $where = ['trade_id' => $trade_id, 'num_iid' => $num_iid, 'item_num' => $item_num];
                        if ($item_id) {
                            $where['id'] = ['gt', $item_id];
                        }
                        $order_item = $orderItemObj->where($where)->order('id')->find();
                        if (!$order_item) {
                            $updateMain = true;
                            $order_item = new TbOrdersItem;
                            $order_item->oid = $order->oid;
                            $order_item->trade_id = $trade_id;
                            $order_item->num_iid = $num_iid;
                            $order_item->seller_shop_title = $seller_shop_title;
                            $order_item->item_title = $v['item_title'];
                            $order_item->item_num = $item_num;
                            $order_item->tb_status = $tb_status;
                            $order_item->pay_price = $pay_price;
                            $order_item->settle_price = $settle_price;
                            $order_item->forecast_income = $forecast_income;
                            $order_item->income_rate = $income_rate;
                            $order_item->income = $income;
                            $order_item->commission_rate = $commission_rate;
                            $order_item->commission_fee = $commission_fee;
                            $order_item->subsidy_rate = $subsidy_rate;
                            $order_item->subsidy_fee = $subsidy_fee;
                            $order_item->divided_rate = $divided_rate;
                            $order_item->earning_time = strtotime($earning_time);
                            $order_item->create_time = strtotime($create_time);
                            $order_item->pid = $pid;
                            $pic_url = db('tb_goods')->where('num_iid', $num_iid)->value('pic_url');
                            $order_item->pic_url = $pic_url ? $pic_url : '';
                            $order_item->save();
                            $insert++;
                            if (session('admin_id')) {
                                model('AdminLog')->adminLogAdd("ID{$order_item->id}，订单号{$order_item->trade_id}，商品名{$order_item->item_title}，状态{TbOrders::$tbStatusList[$tb_status]}", 'add', 'orderItem');
                            }
                        } elseif ($order_item->tb_status != $tb_status || $order_item->pay_price != $pay_price || $order_item->settle_price != $settle_price || $order_item->forecast_income != $forecast_income || $order_item->income != $income || $order_item->commission_fee != $commission_fee || $order_item->subsidy_rate != $subsidy_rate || $order_item->subsidy_fee != $subsidy_fee || $order_item->earning_time != strtotime($earning_time)) {
                            if ($order_item->tb_status != 13) {
                                $order_item->tb_status = $tb_status;
                                $order_item->pay_price = $pay_price;
                                $order_item->settle_price = $settle_price;
                                $order_item->forecast_income = $forecast_income;
                                $order_item->income_rate = $income_rate;
                                $order_item->income = $income;
                                $order_item->commission_rate = $commission_rate;
                                $order_item->commission_fee = $commission_fee;
                                $order_item->subsidy_rate = $subsidy_rate;
                                $order_item->subsidy_fee = $subsidy_fee;
                                $order_item->divided_rate = $divided_rate;
                                $order_item->earning_time = strtotime($earning_time);
                                $order_item->save();
                                $update++;
                                $updateMain = true;
                                if (session('admin_id')) {
                                    model('AdminLog')->adminLogAdd("ID{$order_item->id}，订单号{$order_item->trade_id}，商品名{$order_item->item_title}，状态{TbOrders::$tbStatusList[$tb_status]}", 'update', 'orderItem');
                                }
                            }
                        }
                        $item_id = $order_item->id;
                        $success++;
                    }
                    if ($oid) {
                        $this->updateOrder($oid);
                    }
                    $page++;
                } else {
                    break;
                }
            } elseif ($page == 1) {
                $refresh_session = file_get_contents("http://www.jxb001.cn/openApi/user/refresh/session?appkey=1311360650099917&appsecret=02008dbcb791c23778e0226577451335&refresh_token=" . cache('tb_refresh_token'));
                $refresh_session = json_decode($refresh_session,true);
                if(!$refresh_session['code']){
                    $session = $refresh_session['data']['session'];
                    cache("tb_session",$session);
                    cache("tb_refresh_token",$refresh_session['data']['refresh_token']);
                }
            }else{
                break;
            }
        }
        if($setUnJianggo){
            $wechatObj = new \wechat\WechatApi();
            $un_orders = model('TbOrders')->alias('o')->field('o.trade_id,ue.val')->join('user_extension ue', 'ue.uid=o.uid')->where(['status' => 0, 'extension' => 'openid', 'key' => $wechatObj->getAppid()])->select()->toArray();
            model('TbOrders')->alias('o')->where(['status' => 0])->update(['status' => -1]);
            foreach ($un_orders as $o) {
                $wechatObj->send_wxmsg($o['val'], 'text', '订单号' . $o['trade_id'] . '未经奖购下单，如有疑问请联系客服：zhaoquan618（微信号）');
            }
        }
    }

}
