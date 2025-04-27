<?php
//定时取消订单
set_time_limit(3600);
$root_dir = realpath(dirname(__FILE__) . '/../../');
require_once( $root_dir . '/config/config.php');
require_once( $root_dir . '/config/neigou_config.php');
require ($root_dir . '/app/base/kernel.php');
define('APP_DIR', $root_dir . "/app/");
include_once (APP_DIR . "/base/defined.php"); 
include_once (APP_DIR . "/base/lib/http.php");


include $root_dir.'/NG_PHP_LIB/Curl.php'; //公用curl请求类
include $root_dir.'/NG_PHP_LIB/Logger.php'; //公用loger类
include $root_dir.'/NG_PHP_LIB/ApiClient.php';//curl客户端
include $root_dir.'/NG_PHP_LIB/Config.php';//客户端配置
include $root_dir.'/NG_PHP_LIB/AMQP.php';//客户端配置
include $root_dir.'/NG_PHP_LIB/RedisClient.php';//Redis 队列封装
if (!kernel :: register_autoload()){
    require (APP_DIR . '/base/autoload.php');
}
cachemgr::init(false);

if (!defined('BASE_URL')) {
    if (base_kvstore::instance('setting/base')->fetch('shell_base_url', $shell_base_url)) {
        define('BASE_URL', $shell_base_url);
    }else{
        echo 'Please install ecstore first, and login to the backend ';
    }
}