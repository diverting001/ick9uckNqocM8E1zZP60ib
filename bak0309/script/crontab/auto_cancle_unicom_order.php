<?php
/**
 * @desc 联通分销订单超时未确认自动取消脚本
 * @author daifei<daifei@shopex.cn>
 */
require_once(dirname(__FILE__) . '/../../config/config.php');
#内购独有配置
require('./config/neigou_config.php');
//公用curl请求类
include './NG_PHP_LIB/Curl.php';
//公用loger类
include './NG_PHP_LIB/Logger.php';
//https类
//include './NG_PHP_LIB/UrlProtocalConverter.php';
//curl客户端
include './NG_PHP_LIB/ApiClient.php';
//客户端配置
include './NG_PHP_LIB/Config.php';


require_once(ROOT_DIR.'/app/base/kernel.php');
if(!kernel::register_autoload()){
    require_once(APP_DIR.'/base/autoload.php');
}

//超时自动取消
kernel::single('unicom_service_order_order')->autoCancel();