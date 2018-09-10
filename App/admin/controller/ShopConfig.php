<?php
namespace app\admin\controller;

/**
 * 参数设置
 * @author jiang
 */
class ShopConfig extends Base {

    public function index(){
        $obj = model('ShopConfig'); 
        $configs = $obj->all();
        $this->assign('configs',$configs);
        $this->assign('ur_here', '参数设置');
        return $this->display();
    }
    
    public function save(){
        if(!\think\Validate::token('__token__','',$_POST)){
            return $this->url_redirect('非法数据来源', url("editAction"), "");
        }
        foreach($_REQUEST['code'] as $k=>$v){
            model('ShopConfig')->where(['code'=>$v])->update(['value'=>$_REQUEST['value'][$k]]);
        }
        \lib\cache\CacheTool::configsCache(array(),true);
        return $this->url_redirect('', "index", "参数设置");
    }
}
