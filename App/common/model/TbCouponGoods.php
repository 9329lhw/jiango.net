<?php
namespace app\common\model;

class TbCouponGoods extends \think\Model{
    protected $updateTime = false;
    protected $createTime = false;
    /*
     * @param type $num_iid
     * @return type
     */
    public function getGoodsByNumIid($num_iid,$pid){
        
        $goodsinfo = $this->where(['num_iid'=>$num_iid,'pid'=>$pid])->value("id");
        return $goodsinfo;
    }
    
    public static function getGoodsInfoByNumId($num_iid,$pid){
        return TbCouponGoods::where(['num_iid'=>$num_iid,'pid'=>$pid])
                ->find();
    }
    
    public function saveData($data){
        if($data[$this->getPk()] == ''){
            unset($data[$this->getPk()]);
            if($this->create($data)){
                return "添加成功";
            }else{
                return "添加失败";
            }
        }else{
            $this->isUpdate()->save($data);
            return "编辑成功";
        }
    }
}

