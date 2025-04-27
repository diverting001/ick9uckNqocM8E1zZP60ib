<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<?php


// 使用新 GD 库 解决 PNG 图片黑色背景问题
defined('IMAGE_TOOL') OR define('IMAGE_TOOL', 'imagick');

// 锁
$filename = implode('_',array_filter(explode('/',__FILE__))) . '.pid';
$fp = fopen("/tmp/" . $filename,'w+');
if(!flock($fp,LOCK_EX|LOCK_NB))
    exit('LOCK');

// 重新生成有问题图片
{
    require(dirname(__FILE__) . '/config.php');
    // 重写时间限制
    set_time_limit(0);

    $goodsObject = kernel::single('b2c_goods_object');
    $goodsObject->rebuild_yanxuan_image();
}

//正常解锁
flock($fp,LOCK_UN);
fclose($fp);