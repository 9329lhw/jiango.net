<?php

namespace app\tebiedemingzi\controller;
set_time_limit(0);
class Excel extends Base
{
    public function index(){
        return $this->display();
    }

    public function goodsImport(){  
        import('phpexcel.PHPExcel', EXTEND_PATH);
        //获取表单上传文件
        $file = request()->file('import_file');
        $info = $file->validate(['ext' => 'xls'])->move(ROOT_PATH . 'public' . DS . 'excel');
        $file_name = ROOT_PATH . 'public' . DS . 'excel' . DS . $info->getSaveName();   //上传文件的地址  
        //$objReader = \PHPExcel_IOFactory::createReader('Excel2007'); //xls
        $objReader = \PHPExcel_IOFactory::createReader('Excel5'); //xls
        $excel = $objReader->load($file_name);
        $sheet = $excel->getSheet(0);
        $all_row = $sheet->getHighestRow();
        $actionobj = model('Goods'); 
        
        for ($row = 2; $row <= $all_row; $row++) {
            $num_iid = $sheet->getCell('A' . $row)->getValue(); //商品id
//            $id = $actionobj->getGoodsByNumIid($num_iid);
            if (!empty($num_iid)) {
//                $data['id'] = $id;
                $data['num_iid'] = $num_iid;
                $data['title'] = $sheet->getCell('B' . $row)->getValue(); //商品名称
                $data['pic_url'] = $sheet->getCell('C' . $row)->getValue(); //图片
                $data['tbk_url'] = $sheet->getCell('F' . $row)->getValue(); //tbk链接
                $data['price'] = $sheet->getCell('G' . $row)->getValue(); //tbk链接
                $data['sale_num'] = $sheet->getCell('H' . $row)->getValue(); //tbk链接
                $data['commission_rate'] = $sheet->getCell('I' . $row)->getValue(); //tbk链接
                $data['commission'] = $sheet->getCell('J' . $row)->getValue(); //tbk链接
                $data['tg_url'] = $tg_link = $sheet->getCell('U' . $row)->getValue(); //推广链接
                $data['coupon_start_time'] = strtotime($sheet->getCell('R' . $row)->getValue()); //开始时间
                $data['coupon_end_time'] = strtotime($sheet->getCell('S' . $row)->getValue()); //结束时间
                $data['item_url'] = $sheet->getCell('D' . $row)->getValue();
                $actionobj->saveData($data);
            }
        }
        return $this->url_redirect(1, "/tebiedemingzi/goods/index", "商品列表");    
    }
}

