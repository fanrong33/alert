<?php
/**
 * 记住我 工具类
 * @author 蔡繁荣 <fanrong33@qq.com>
 * @version 1.0.1 build 20170204
 */
class RemeberMe {

    /**
     * 设置记住我到cookie
     * @param  string  $name    cookie名称
     * @param  string  $value   待混淆加密的cookie值
     * @param  integer $expire  过期时间，86400=10天
     */
    public static function set($name, $value, $expire=864000) {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $salt       = C('AUTH_SALT');
        $encode     = md5($value.$user_agent.$salt);
        
        $cookie_string = $value.','.$encode;
        cookie($name, $cookie_string, $expire);
    }

    public static function get($name){
        $cookie_string = cookie($name);
        if($cookie_string){
            
            list($value, $cookie_encode) = explode(',', $cookie_string);

            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $salt       = C('AUTH_SALT');
            $encode     = md5($value.$user_agent.$salt);

            if($cookie_encode == $encode){
                return $value;
            }
        }

        return '';
    }

}


?>