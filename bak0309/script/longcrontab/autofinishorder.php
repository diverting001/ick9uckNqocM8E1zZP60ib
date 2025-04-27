<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<?php

set_time_limit(0);
// 锁
$filename = implode('_',array_filter(explode('/',__FILE__))) . '.pid';
$fp = fopen("/tmp/" . $filename,'w+');
if(!flock($fp,LOCK_EX|LOCK_NB))
    exit('LOCK');

// 订单自动完成
{
    require(dirname(__FILE__) . '/config.php');
    kernel::single('b2c_order_autofinish') -> generate();
}

//正常解锁
flock($fp,LOCK_UN);
fclose($fp);