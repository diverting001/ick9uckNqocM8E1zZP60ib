<?php
$host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
$_SERVER['HTTP_HOST'] = $host;

#微信的静默获取OPEN_ID
// require('./plugin/wxauth.php');
#内购独有配置
require('./config/neigou_config.php');
//公用curl请求类
include './NG_PHP_LIB/Curl.php';
//公用loger类
include './NG_PHP_LIB/Logger.php';
//curl客户端
include './NG_PHP_LIB/ApiClient.php';
//客户端配置
include './NG_PHP_LIB/Config.php';
//消息队列
include './NG_PHP_LIB/AMQP.php';

//Redis队列
include './NG_PHP_LIB/RedisClient.php';

//通用Redis
include './NG_PHP_LIB/BaseRedis.php';

if (!array_key_exists('cookie_support', $_COOKIE)){
    setcookie('cookie_support', 'yes', null, '/');
}

define('ROOT_DIR',realpath(dirname(__FILE__)));
require(ROOT_DIR.'/app/base/kernel.php');
kernel::boot();

if(defined("STRESS_TESTING")){
    b2c_forStressTest::logSqlAmount();
}
