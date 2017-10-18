<?php
return array(
    // URL路由规则，使用Dispatcher重写pathinfo来进行URL路由
    'URL_ROUTE_RULES'  =>  array(
        // 访问地址http://www.xxx.com/index.php/code/detail/ident/3
        // 短地址http://www.xxx.com/code/3
        // 短链接php里 U('code/3'); html里 {:U('code/3')}
        "/^\/code\/(\w{9})$/i" => "/code/detail/ident/\${1}",

        // 访问地址http://www.xxx.com/index.php/code/show/ident/3
        // 短地址http://www.xxx.com/show/3
        // 短链接php里 U('show/3'); html里 {:U('show/3')}
        "/^\/show\/(\w{9})$/i" => "/code/show/ident/\${1}",
    ),
);
?>