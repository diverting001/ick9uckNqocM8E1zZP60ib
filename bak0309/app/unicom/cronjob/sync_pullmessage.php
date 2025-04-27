<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<?php
/**
 *   拉取联通消息
 */
require(dirname(__FILE__) . '/config.php');

echo 'UNICOM PULL Message START'.PHP_EOL;
 
$typeArr = array(1, 2, 3, 4, 5);
$is_del = false;
$type = $argv[1];
if (in_array($type, $typeArr)) {
    if($type == 5) {
        $is_del=true;
    }
    $s = kernel::single("unicom_cron_pullmessage")->pullUnicomMessage($type, $is_del);
    var_dump($s);
}

echo 'UNICOM PULL Message END'.PHP_EOL;