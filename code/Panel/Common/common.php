<?php


/**
 * Discuz! 经典加密解密函数
 * @param  string  $string      明文 或 密文
 * @param  string  $operation   DECODE表示解密,其它表示加密
 * @param  string  $key         密钥
 * @param  integer $expiry      密文有效期
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    $ckey_length = 4;

    // 密匙
    $key = md5($key ? $key : '.#xb#.');

    // 密匙a会参与加解密
    $keya = md5(substr($key, 0, 16));
    // 密匙b会用来做数据完整性验证
    $keyb = md5(substr($key, 16, 16));
    // 密匙c用于变化生成的密文

    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
    // 参与运算的密匙
    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);
    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    // 产生密匙簿
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];

        $box[$j] = $tmp;
    }
    // 核心加解密部分
    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];

        $box[$j] = $tmp;
        // 从密匙簿得出密匙进行异或，再转成字符
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if($operation == 'DECODE') {
        // substr($result, 0, 10) == 0 验证数据有效性
        // substr($result, 0, 10) - time() > 0 验证数据有效性
        // substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性
        // 验证数据有效性，请看未加密明文的格式
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
        return $keyc.str_replace('=', '', base64_encode($result));
    }
}


/**
 * 数据导出csv文件（fputcsv）
 * 依赖 import('@.ORG.Net.Http');
 * @param array $options = > array(
 *  'list'       => array    列表，不包含key
 *  'head'       => array    Excel列表信息
 *  'save_path'  => string   导出文件保存的路径，以/结尾
 *  'save_name'  => string   文件保存的名称
 *  'download'   => boolean  是否下载
 * )
 */
function export_csv($options){
    set_time_limit(0);

    $list      = $options['list'];
    $head      = $options['head'];
    $save_path = $options['save_path'];
    $save_name = $options['save_name'];
    $download  = $options['download'];

    // 1、创建文件并输出列名到csv文件中

    if(!is_dir($save_path)){
        mkdir($save_path, 0755, true);
    }

    $file = $save_path.$save_name;
    if(!is_file($file)){

        $fp = fopen($file, 'a');

        // 输出Excel列名信息
        foreach ($head as $k => $v) {
            // CSV的Excel支持GBK编码，一定要转换，否则乱码
            $head[$k] = mb_convert_encoding(trim($v), 'gbk', 'utf-8');
        }

        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $head);
    }else{
        $fp = fopen($file, 'a');
    }

    // 2、分段写入内容到文件
    $number = 0; // 计数器
    $limit = 2000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小

    // 逐行取出数据，不浪费内存
    $count = count($list);

    for($j=0; $j<$count; $j++) {

        $number++;
        if ($limit == $count) { //刷新一下输出buffer，防止由于数据过多造成问题
            ob_flush();
            flush();
            $number = 0;
        }
        $row[] = $list[$j];

        foreach ($row as $i => $v) {
            // $row[$i] = mb_convert_encoding(trim($v),'gbk','utf-8');
            fputcsv($fp, $row[$i]);
        }
        unset($row);
    }
    fclose($fp);

    if($download){
        import('@.ORG.Net.Http');
        Http::download($file);
    }
}


/**
 * 获取基础的缓存对象（单对象）
 * @param string $name 模型名
 * @param integer $primary_id 主键ID值 or array $cond
 * @param integer $expire 缓存有效期（秒）
 * @author 蔡繁荣
 * @version 1.2.0 build 20170407
 */
function cache_get($name , $primary_id , $expire=null){
    $result = null;

    $cache_key = generate_cache_key($name, $primary_id);
    $result = S($cache_key);
    if(!$result){
        if(is_array($primary_id)){
            $result = M($name)->where($primary_id)->find();
        }else{
            $result = M($name)->find($primary_id);
        }

        // 默认缓存3600秒
        $expire = is_null($expire) ? 3600 : $expire;
        S($cache_key, $result, $expire);
    }
    return $result;
}

/**
 * 获取基础的缓存对象（单对象）
 * @param string $name 模型名
 * @param integer $primary_id 主键ID值
 * @param integer $expire 缓存有效期（秒）
 * @author 蔡繁荣
 * @version 1.2.0 build 20170407
 */
function cache_get_list($name, $params, $expire=null){
    $result = array();

    $cache_key = generate_cache_key($name, 'list', $params);
    $result = S($cache_key);
    if(!$result){
        $result = M($name)->where($params)->select();

        // 默认缓存60秒，列表缓存时间少些
        $expire = is_null($expire) ? 60 : $expire;
        S($cache_key, $result, $expire);
    }

    return $result;
}


/**
 * 删除缓存对象（单对象）
 * @author 蔡繁荣
 * @version 1.2.0 build 20170407
 */
function cache_rm($name, $primary_id) {
    $cache_key = generate_cache_key($name, $primary_id);
    S($cache_key, null);
}


/**
 * 根据变量生成缓存的cache_key
 * 用法：generate_cache_key('product', 1, ...)   // cache_product_1_xxxxxx
 * @author 蔡繁荣
 * @version 1.2.1 build 20170408
 */
function generate_cache_key(){
    $args = func_get_args();
    foreach ($args as $key => $arg) {
        if(is_array($arg)){
            ksort($arg); // 参数排序，减少影响

            $tmp = '';
            foreach ($arg as $arg_key => $arg_val) {
                $tmp = $arg_key.'_'.$arg_val;
            }
            $args[$key] = $tmp;
        }
    }
    
    array_unshift($args, 'cache');
    $cache_key = join('_', $args);
    return $cache_key;
}



/**
 * 密码加密方法
 * @param string $password
 * @param string $salt
 */
function encrypt_pwd($password, $salt){
    //TODO 暂时在后端进行加密，为前端未来加密传输预留
    $password = md5($password);
    $salt = C('AUTH_SALT');
    if(!empty($salt)){
        $password = md5($password.$salt);
    }
    return md5(crypt($password , substr($password , 0, 2)));
}



/**
 * 将索引数组转化为以某键的值为索引的数组
 * @param array  $list 要进行转换的数据集
 * @param string $key  以该key为索引
 */
function array_to_map($list, $key='id'){
    $result = array();
    if(is_array($list)){
        foreach($list as $rs){
            $result[$rs[$key]] = $rs;
        }
    }
    return $result;
}


/**
 * 搜索高亮显示关键字
 *
 * @param string $string 原字符串
 * @param string $keyword 搜索关键字字符串，默认为keyword, 可不传
 *
 * @return string $string 返回高亮后的字符串
 */
function highlight($string , $keyword =''){
    if($keyword == ''){
        $keyword = 'keyword' ; // 默认搜索关键字为 keyword
    }

    if(isset($_GET[$keyword]) && $_GET[$keyword]){
        $keyword_value = $_GET[$keyword];
        return preg_replace ("/($keyword_value)/i", "<span style=\"color:#dd4b39\">\\1</span>", $string);
    }
    return $string;
}


/**
 * 生成guid
 */
function generate_guid() {
    $charid = strtoupper(md5(uniqid(mt_rand(), true)));
    $hyphen = chr(45);// "-"
    $uuid = //chr(123)// "{"
    substr($charid, 0, 8).$hyphen
    .substr($charid, 8, 4).$hyphen
    .substr($charid,12, 4).$hyphen
    .substr($charid,16, 4).$hyphen
    .substr($charid,20,12);
    // .chr(125);// "}"
    return strtolower($uuid);
}


/**
 * 获取当前页面完整URL地址
 * 该函数需要下面的函数进行配合：safe_replace
 * @return type 地址
 */
function get_url() {
    $sys_protocal = isset($_SERVER ['SERVER_PORT']) && $_SERVER['SERVER_PORT' ] == '443' ? 'https://' : 'http://' ;
    $php_self = $_SERVER['PHP_SELF' ] ? safe_replace($_SERVER['PHP_SELF' ]) : safe_replace($_SERVER['SCRIPT_NAME' ]);
    $path_info = isset($_SERVER ['PATH_INFO']) ? safe_replace($_SERVER['PATH_INFO' ]) : '';
    $relate_url = isset($_SERVER ['REQUEST_URI']) ? safe_replace($_SERVER['REQUEST_URI' ]) : $php_self . (isset($_SERVER ['QUERY_STRING']) ? '?' . safe_replace($_SERVER['QUERY_STRING' ]) : $path_info);
    return $sys_protocal . (isset ($_SERVER ['HTTP_HOST']) ? $_SERVER['HTTP_HOST' ] : '') . $relate_url;
}


/**
 * 安全过滤函数
 * @param $string
 * @return string
 */
function safe_replace($string ) {
    $string = str_replace('%20' , '', $string);
    $string = str_replace('%27' , '', $string);
    $string = str_replace('%2527' , '', $string);
    $string = str_replace('*' , '', $string);
    $string = str_replace('"' , '"', $string);
    $string = str_replace("'" , '', $string);
    $string = str_replace('"' , '', $string);
    $string = str_replace(';' , '', $string);
    $string = str_replace('<' , '<', $string);
    $string = str_replace('>' , '>', $string);
    $string = str_replace("{" , '', $string);
    $string = str_replace('}' , '', $string);
    $string = str_replace('\\' , '', $string);
    return $string;
}


/**
 * 获取当前项目域名,末尾不包含/
 */
function get_domain(){
    /* 协议 */
    $protocol = ( isset($_SERVER ['HTTPS']) && (strtolower( $_SERVER ['HTTPS']) != 'off' )) ? 'https://' : 'http://' ;

    /* 域名或IP地址 */
    if ( isset($_SERVER ['HTTP_X_FORWARDED_HOST'])) {
        $host = $_SERVER ['HTTP_X_FORWARDED_HOST'];
    } elseif ( isset($_SERVER ['HTTP_HOST'])) {
        $host = $_SERVER ['HTTP_HOST'];
    } else {
        /* 端口 */
        if (isset ($_SERVER ['SERVER_PORT'])) {
            $port = ':' . $_SERVER[ 'SERVER_PORT'];
            if ((':80' == $port && 'http://' == $protocol ) || (':443' == $port && 'https://' == $protocol)) {
                $port = '' ;
            }
        } else {
            $port = '' ;
        }

        if (isset ($_SERVER ['SERVER_NAME'])) {
            $host = $_SERVER ['SERVER_NAME'] . $port;
        } elseif (isset ($_SERVER ['SERVER_ADDR'])) {
            $host = $_SERVER ['SERVER_ADDR'] . $port;
        }
    }

    return $protocol . $host ;
}


/**
 * timestamp转换成显示时间格式
 * @param $timestamp
 * @return unknown_type
 */
function friendly_time_format($timestamp)
{
    $curTime = time();
    $space = $curTime - $timestamp ;
    //1分钟
    if( $space < 60)
    {
        $string = "刚刚" ;
        return $string ;
    }
    elseif( $space < 3600) //一小时前
    {
        $string = floor ($space / 60) . " 分钟前";
        return $string ;
    }
    elseif( $space < 86400){ // 24小时前
      $string = floor($space /3600) . " 小时前";
      return $string;
    }
    $curtimeArray = getdate($curTime );
    $timeArray = getDate($timestamp );
    if( $curtimeArray['year' ] == $timeArray[ 'year'])
    {
        if($curtimeArray ['yday'] == $timeArray[ 'yday'])
        {
            $format = "%H:%M" ;
            $string = strftime ($format , $timestamp );
            return "今天 { $string}";
        }
        elseif(($curtimeArray ['yday'] - 1) == $timeArray[ 'yday'])
        {
            $format = "%H:%M" ;
            $string = strftime ($format , $timestamp );
            return "昨天 { $string}";
        }
        else
        {
            $string = sprintf ("%d月%d日 %02d:%02d" , $timeArray ['mon'], $timeArray['mday' ], $timeArray[ 'hours'],
            $timeArray['minutes' ]);
            return $string ;
        }
    }
    $string = sprintf("%d年%d月%d日 %02d:%02d" , $timeArray ['year'], $timeArray['mon' ], $timeArray[ 'mday'],
    $timeArray[ 'hours'], $timeArray ['minutes']);
    return $string;
}


?>