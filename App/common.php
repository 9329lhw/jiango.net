<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件


/**
 * 日志
 */
function logResult($word = '',$file = '') {
    //日志存储按天存
    $logpath = "/data/".(SERVER_PATH == 'https://51yuexue.com'?'jianggo_test':'jianggo')."/log/".$file;
    $logfile = $logpath . date('Ymd') . '.txt';
    $fp = fopen($logfile, "a");
    flock($fp, LOCK_EX);
    fwrite($fp, "执行日期：" . strftime("%Y%m%d%H%M%S", time()) . "\n" . $word . "\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}

/**
 * 对象 转 数组
 *
 * @param object $obj 对象
 * @return array
 */
function object_to_array($obj) {
    $obj = (array)$obj;
    foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
            return;
        }
        if (gettype($v) == 'object' || gettype($v) == 'array') {
            $obj[$k] = (array)object_to_array($v);
        }
    }
 
    return $obj;
}

function substr_title($str) {
    if (mb_strwidth($str, 'utf8') > 25) {
        // 此处设定从0开始截取，取10个追加...，使用utf8编码
        // 注意追加的...也会被计算到长度之内
        $str = mb_strimwidth($str, 0, 25, '', 'utf8');
    }
    return $str;
}

/**
 * 数组维度
 * @param type $arr
 * @return type
 */
function arrayLevel($arr){ 
    $al = array(0); 
    function aL($arr,&$al,$level=0){ 
        if(is_array($arr)){ 
            $level++; 
            $al[] = $level; 
            foreach($arr as $v){ 
                aL($v,$al,$level); 
            } 
        } 
    } 
    aL($arr,$al); 
    return max($al); 
} 

/**
 * 截取字符串中的数字
 * @param type $str
 * @return type
 */
function findNum($str=''){
    $num_str = preg_replace('/\D/s', '-', $str);
    $num = trim($num_str,'---');
    $num_arr = explode('-', $num);
    $price = [];
    foreach ($num_arr as $val){
        if($val){
            $price[]=$val;
        }
    }
    
    if($price[1]){
        return $price[1];
    }else{
        return $price[0];
    }
}

/**
 * 
 * @staticvar array $result
 * @param type $cache_name
 * @param type $type
 * @return boolean|array
 */
function read_cache($cache_name,$type=1){
    if(empty($cache_name)){
        return false;
    }
    static $result = array();
    if (!empty($result[$cache_name])) {
        return $result[$cache_name];
    }
    if ($type == 1) {
        $cache_file_path = ROOT_PATH . '/extend/lib/cache/' . $cache_name . '.php';
        if (file_exists($cache_file_path)) {
            include_once($cache_file_path);
            $result[$cache_name] = $data;
            return $result[$cache_name];
        } else {
            return false;
        }
    }
}
/**
 * 
 * @param type $cache_name 缓存名称
 * @param type $cache_date 缓存数据
 * @param type $type 缓存类型 1文件缓存 2redis缓存
 * @param type $path 缓存路径
 * @return boolean
 */
function write_cache($cache_name,$cache_date,$type=1, $path=''){
    $path = $path?:ROOT_PATH. '/extend/lib/cache/';
    if(empty($cache_name)){
        return false;
    }
    if(empty($cache_date)){
        return false;
    }
    if ($type == 1) {
        $cache_file_path = $path. $cache_name . '.php';
        $content = "<?php\r\n";
        $content .= "\$data = " . var_export($cache_date, true) . ";\r\n";
        $content .= "?>";
        file_put_contents($cache_file_path, $content, LOCK_EX);
    }
    if($type == 2){
        
    }
}

//---------------以下为加密函数（复制过去就行了）-----------------
//http://blog.csdn.net/llf369477769/article/details/51837546
//https://www.cnblogs.com/phpfensi/p/4648464.html
function keyED($txt,$encrypt_key){       
    $encrypt_key =    md5($encrypt_key);
    $ctr=0;       
    $tmp = "";       
    for($i=0;$i<strlen($txt);$i++)       
    {           
        if ($ctr==strlen($encrypt_key))
        $ctr=0;           
        $tmp.= substr($txt,$i,1) ^ substr($encrypt_key,$ctr,1);
        $ctr++;       
    }       
    return $tmp;   
}    
function encrypt($txt,$key)   {
    $encrypt_key = md5(mt_rand(0,20));
    $ctr=0;       
    $tmp = "";      
     for ($i=0;$i<strlen($txt);$i++)       
     {
        if ($ctr==strlen($encrypt_key))
            $ctr=0;           
        $tmp.=substr($encrypt_key,$ctr,1) . (substr($txt,$i,1) ^ substr($encrypt_key,$ctr,1));
        $ctr++;       
     }       
     return keyED($tmp,$key);
} 
    
function decrypt($txt,$key){       
    $txt = keyED($txt,$key);       
    $tmp = "";       
    for($i=0;$i<strlen($txt);$i++)       
    {           
        $md5 = substr($txt,$i,1);
        $i++;           
        $tmp.= (substr($txt,$i,1) ^ $md5);       
    }       
    return $tmp;
}
function encrypt_url($url,$key){
    return rawurlencode(base64_encode(encrypt($url,$key)));
}
function decrypt_url($url,$key){
    return decrypt(base64_decode(rawurldecode($url)),$key);
}
function geturl($str,$key){
    $str = decrypt_url($str,$key);
    $url_array = explode('&',$str);
    if (is_array($url_array))
    {
        foreach ($url_array as $var)
        {
            $var_array = explode("=",$var);
            $vars[$var_array[0]]=$var_array[1];
        }
    }
    return $vars;
}
 
//---------------以上为加密函数-结束（复制过去就行了）-----------------
//$param = base64_encode('cp='.$list[$key]['coupon_price'].'&p='.$list[$key]['price'].'&cn='.$val['coupon_id'].'&pu='.$val['pic_url'].'&t='.$val['title'].
//'&op='.$list[$key]['org_price'].'&nd='.$val['num_iid'].'uid='.$this->uid.'&pid='.$this->pid);
function geturlnew($str){
    $de_str = base64_decode(str_replace(" ","+",$str));
    $url_array = explode('&',$de_str);
    if (is_array($url_array))
    {
        foreach ($url_array as $var)
        {
            $var_array = explode("=",$var);
            $vars[$var_array[0]]=$var_array[1];
        }
    }
    return $vars;
}


/**
 * 获取分类类表
 * @param type $type 1,首页  2,搜索页
 * @return string
 */
function get_category_by_type($type){
    $cate = read_cache('category');
    foreach ($cate as $key => $val){
        $cate[$key]['extimg'] = IMG_PATH.$val['extimg'];
        if($cate[$key]['board_id'] == $type){
            unset($cate[$key]['board_id']);
            $date[] = $cate[$key];
        }
    }
    return $date;
}

function get_device_version($device){
    $version = read_cache('app_seting');
    if(!empty($version)){
        return $version[$device];
    }else{
        return '';
    }
}

/**
 * 过滤微信昵称中的表情（不过滤 HTML 符号）
 */
function filterNickname($nickname) {
    $nickname = preg_replace_callback( '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $nickname);
    return addslashes(trim($nickname));

//    $nickname = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $nickname);
//    $nickname = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $nickname);
//    $nickname = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $nickname);
//    $nickname = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $nickname);
//    $nickname = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $nickname);
//    $nickname = str_replace(array('"', '\''), '', $nickname);
//    return addslashes(trim($nickname));
    
}


/**
 * 过滤微信昵称中的表情（不过滤 HTML 符号）
 */
function getLinkParam($url) {
    $url_arr = explode('&', $url);
    $result = [];
    if(!empty($url_arr)){
        foreach ($url_arr as $key=>$val)
        $param = explode('=', $val);
        $result[$param[0]]=$param[1];
    }
    return $result;
}