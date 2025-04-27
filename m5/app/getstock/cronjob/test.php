<?php
/**
 *
 * 从MQ中拉取订单支付成功消息，依据消息处理订单支付成功后的任务：优惠券、免邮券、积分、o2o发码、发通知
 *
 */

require(dirname(__FILE__) . '/config.php');



$order_info = kernel::single("b2c_service_order")->getOrderInfo("202502270424165098");

$order_confirm_finished = kernel::single("b2c_service_orderconfirmfinished");
$order_confirm_finished->order_confirm_finish($order_info);

