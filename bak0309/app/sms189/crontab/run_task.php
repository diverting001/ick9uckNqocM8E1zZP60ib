<?php
//触发短信发送
set_time_limit(3600);
$root_dir = realpath(dirname(__FILE__) . '/../../../');
require_once( $root_dir . '/config/config.php');
require ($root_dir . '/app/base/kernel.php');
define('APP_DIR', $root_dir . "/app/");
include_once (APP_DIR . "/base/defined.php"); 
include_once (APP_DIR . "/base/lib/http.php");
if (!kernel :: register_autoload()){
    require (APP_DIR . '/base/autoload.php');
}
cachemgr::init(false);

 echo '['.date('Y-m-d H:i:s',time())."]begin task...<br/>";

  kernel::single('sms189_cronjob_sms')->flush_queue();
 
echo '['.date('Y-m-d H:i:s',time())."]end task...<br/>";