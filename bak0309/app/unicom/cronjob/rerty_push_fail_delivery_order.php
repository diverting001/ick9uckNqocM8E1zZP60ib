<?php

/**
 * 重新推送失败的发货单到联通（额外补偿）
 * 
 */
require(dirname(__FILE__) . '/config.php');


kernel::single("unicom_order_pushmessage")->retryPushFailDeliveryOrders();


/*
$order_id = '201810121922376889';
//$order_info = kernel::single("unicom_service_order_order")->getOrderInfo($order_id);
$process_result = kernel::single("unicom_order_pushmessage")->deliveryOrder($order_id);
var_dump($process_result);
exit();
*/

