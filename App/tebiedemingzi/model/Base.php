<?php

namespace app\tebiedemingzi\model;

use think\Model;

class Base extends Model{
    
    /**
     * ajax返回错误信息
     */	
    public function ajaxError($error = '', $content = '', $message = '') {
        switch ($error) {
            case 0:
                return array('error' => 0, 'content' => $content);
                break;
            case 1:
                return array('error' => 1, 'message' => '非法操作');
                break;
            case 2:
                return array('error' => 2, 'message' => '编辑信息不能为空！');
                break;
            case 3:
                return array('error' => 3, 'message' => '编辑失败，请联系管理员！');
                break;
            case 4:
                return array('error' => 4, 'message' => '数据不存在');
                break;
            default :
                return array('error' => $error, 'message' => $message, 'content' => $content);
        }
    }
    /**
     * 保存
     */
    public function saveData($data){
        if($data[$this->getPk()] == ''){
            unset($data[$this->getPk()]);
            if($this->create($data)){
                return "添加成功";
            }else{
                return "添加成功";
            }
        }else{
            $this->isUpdate()->save($data);
            return "编辑成功";
        }
    }
}
