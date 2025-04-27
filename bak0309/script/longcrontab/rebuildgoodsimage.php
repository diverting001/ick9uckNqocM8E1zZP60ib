<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<?php

set_time_limit(0);
// 锁
$filename = implode('_',array_filter(explode('/',__FILE__))) . '.pid';
$fp = fopen("/tmp/" . $filename,'w+');
if(!flock($fp,LOCK_EX|LOCK_NB))
    exit('LOCK');

// 重新生成有问题图片
{
    require(dirname(__FILE__) . '/config.php');

    $goodsObject = kernel::single('b2c_goods_object');
    $goodsObject->rebuild_error_image();
}

//正常解锁
flock($fp,LOCK_UN);
fclose($fp);