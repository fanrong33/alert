<?php
// 开启调试模式
define('APP_DEBUG', true); // 正式环境注释 TODO!!!

//定义项目名称和路径
define('APP_NAME'   , 'Api');
define('APP_PATH'   , '../Api/');
// 定义项目根路径
define('ROOT_PATH'  , rtrim(dirname(__FILE__), '/\\') . DIRECTORY_SEPARATOR);
define('THINK_PATH' , '../ThinkPHP/');

// 定义Composer vendor目录
define('VENDOR_PATH', '../ThinkPHP/vendor/');

// 加载框架入口文件
require(THINK_PATH."ThinkPHP.php");
?>