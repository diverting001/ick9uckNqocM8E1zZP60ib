<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<?php

set_time_limit(0);
// 锁
$filename = implode('_',array_filter(explode('/',__FILE__))) . '.pid';
$fp = fopen("/tmp/" . $filename,'w+');
if(!flock($fp,LOCK_EX|LOCK_NB))
    exit('LOCK');

// 排查可疑的发货单
{
    require(dirname(__FILE__) . '/config.php');

    $objB2c_delivery = b2c_order_delivery::getInstance(app::get('b2c'), app::get('b2c')->model('delivery'));
    if (!empty($objB2c_delivery)) {
        $objB2c_delivery->check_suspicious_delivery();
    }
}

//正常解锁
flock($fp,LOCK_UN);
fclose($fp);