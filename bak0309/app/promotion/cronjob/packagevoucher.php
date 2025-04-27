<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<?php
/**
 * 重试同步移动订单和发货状态失败的任务
* 暂时不自动执行
*/
require(dirname(__FILE__) . '/config.php');
$obj = kernel::single('promotion_voucher_memvoucher');
$obj->applyAllcompanyPkg();
