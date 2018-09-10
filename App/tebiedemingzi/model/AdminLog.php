<?php
namespace app\tebiedemingzi\model;

/**
 * 后台管理员日志
 */
class AdminLog extends Base{
    
    protected $updateTime = false;
    
    private $action = [
        'add'=>'添加',
        'edit'=>'编辑',
        'remove'=>'删除',
        'setup'=>'设置',
        'cancel'=>'取消',
        'update'=>'更新',
    ];
    private $module = [
        'user'=>'用户',
        'boss_agent'=>'特殊代理',
        'commission'=>'分佣',
        'prize'=>'开奖',
        'order'=>'父订单',
        'orderItem'=>'子订单',
    ];
    
    /**
     * 记录管理员的操作内容
     * @param   string      $sn         数据的唯一值
     * @param   string      $action     操作的类型
     * @param   string      $content    操作的内容
     */
    public function adminLogAdd($sn, $action, $module) {
        $data['user_id'] = session('admin_id');
        $data['log_info'] = ($this->action[$action]?$this->action[$action]:$action) . ($this->module[$module]?$this->module[$module]:$module) . '： ' . $sn;
        $data['ip_address'] = realIp();
        return $this->save($data);
    }
}

