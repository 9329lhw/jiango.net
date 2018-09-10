<?php
namespace app\common\model;

class TbGoods extends \think\Model{
    protected $updateTime = false;
    protected $createTime = false;
    /*
     * @param type $num_iid
     * @return type
     */
    public function getGoodsByNumIid($num_iid){
        
        $goodsinfo = $this->where(['num_iid'=>$num_iid])->value("id");
        return $goodsinfo;
    }
    
    public static function getGoodsInfoByNumId($numid){
        return TbGoods::where(['num_iid'=>$numid])->find();
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

