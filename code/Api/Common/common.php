<?php


/**
 * 获取基础的缓存对象（单对象）
 * @param string $name 模型名
 * @param integer $primary_id or $array  主键ID值 or 条件array
 * @param integer $expire 缓存有效期（秒）
 * @version 1.0.2 build 20170421
 */
function cache_get($name , $primary_id , $expire=null){
    $result = null;

    $cache_key = generate_cache_key($name, $primary_id);
    $result = S($cache_key);
    if(!$result){
        if(is_array($primary_id)){
            $result = D($name)->where($primary_id)->find();
        }else{
            $result = D($name)->find($primary_id);
        }
        // 默认缓存3600秒
        $expire = is_null($expire) ? 3600 : $expire;
        S($cache_key, $result, $expire);
    }
    return $result;
}

/**
 * 删除缓存对象（单对象）
 * @version 1.0.0 build 20170421
 */
function cache_rm($name, $primary_id) {
    $cache_key = generate_cache_key($name, $primary_id);
    S($cache_key, null);
}

/**
 * 根据变量生成缓存的cache_key
 *
 * 用法：generate_cache_key('product', 1, ...)   // cache_product_1_xxxxxx
 * @version 1.1.0 build 20170711
 */
function generate_cache_key(){
    $args = func_get_args();
    $list = array();
    foreach ($args as $key => $arg){
        if(is_array($arg)){
            foreach ($arg as $arg_key => $arg_val) {
                if(is_array($arg_val)){
                    $list[] = $arg_key.'_'.join('_', $arg_val);
                }else{
                    $list[] = $arg_key.'_'.$arg_val;
                }
            }
        }else{
            $list[] = $arg;
        }
    }
    array_unshift($list, 'cache');
    $cache_key = join('_', $list);
    return $cache_key;
}