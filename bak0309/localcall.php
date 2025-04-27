<?php
error_reporting(0);
set_time_limit(120);
$root_dir = realpath(dirname(__FILE__) . '/');
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

if (!kernel :: register_autoload()){
    require (APP_DIR . '/base/autoload.php');
}
cachemgr::init(false);

if(!isset($argv[1])){
    stdout('failed','params not found');
}

list($params[0],$params[1],$params[2],$params[3]) = explode('/',$argv[1],4);
$obj = kernel::single($params[0]);
if(!is_object($obj)){
    stdout('failed','class not found');
}
if(!method_exists($obj,$params[1])){
    stdout('failed','method not found');
}

$data = json_decode(base64_decode($params[3]), true);

try{
    $_method = new \ReflectionMethod($obj,$data['method']);
}catch(ReflectionException $e){
    stdout('failed','Rpc method not found');
}

$params_method = $_method -> getParameters();
$temp = array();
foreach ($params_method as $k => $v) {
    $temp[$k] = $v -> name;
}
$params_method = $temp;

$temp = array();
foreach ($params_method as $k => $v) {
    $temp[$k] = $data['params'][$v];
}

$result = call_user_func_array(array($obj,$params[1]),$temp);
$result = json_encode($result);
$result = base64_encode($result);

stdout('success',$result);


function stdout($is_success, $msg){
    print json_encode(array(
        'result'=> $is_success ? 'success' : 'failed',
        'msg'=> is_string($msg) ? $msg : json_encode($msg)
    ));
    exit;
}