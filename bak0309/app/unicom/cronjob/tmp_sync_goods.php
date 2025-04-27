<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<?php
/**
 *   同步联通地址信息
 */
require(dirname(__FILE__) . '/config.php');

echo 'UNICOM PUSH GOODS START';


$errMsg = '';
$bns = explode(',', 'YXKA-1108060');
foreach ($bns as $bn)
{
    $errMsg = '';
    // all,base,price,marketable,image,spec,related
    var_dump(kernel::single("unicom_goods")->pushGoods($bn, 'all', $errMsg));
    var_dump($errMsg);
}


echo 'UNICOM PUSH GOODS END';
