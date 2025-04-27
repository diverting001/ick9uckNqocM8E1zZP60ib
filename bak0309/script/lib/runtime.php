<?php

ob_implicit_flush(1);
$root_dir = realpath(dirname(__FILE__).'/../../');

require($root_dir."/config/config.php");

require(APP_DIR.'/base/kernel.php');
@require(APP_DIR.'/base/defined.php');

require_once( $root_dir . '/config/neigou_config.php');
include $root_dir.'/NG_PHP_LIB/Curl.php'; //公用curl请求类
include $root_dir.'/NG_PHP_LIB/Logger.php'; //公用loger类
include $root_dir.'/NG_PHP_LIB/ApiClient.php';//curl客户端
include $root_dir.'/NG_PHP_LIB/Config.php';//客户端配置

if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}
cachemgr::init(false);

// 时区设置
date_default_timezone_set(
    defined('DEFAULT_TIMEZONE') ? ('Etc/GMT'.(DEFAULT_TIMEZONE>=0?(DEFAULT_TIMEZONE*-1):'+'.(DEFAULT_TIMEZONE*-1))):'UTC'
);

if (!defined('BASE_URL')) {
    if (base_kvstore::instance('setting/base')->fetch('shell_base_url', $shell_base_url)) {
        define('BASE_URL', $shell_base_url);
    }else{
        echo 'Please install ecstore first, and login to the backend ';
    }
 }


