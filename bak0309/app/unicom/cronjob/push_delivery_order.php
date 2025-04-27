<?php

/**
 * 供应商根据业务订单号，向联通平台推送发货单信息
 * 
 */
require(dirname(__FILE__) . '/config.php');
/*
  $order_id = '201808071405562438';
  //$order_info = kernel::single("unicom_service_order_order")->getOrderInfo($order_id);
  $process_result = kernel::single("unicom_order_pushmessage")->deliveryOrder($order_id);
  var_dump($process_result);
  exit();
 * 
 */
try {
    $fun = function ($msg) {
        if (!isset($msg['data']['order_id']) || empty($msg['data']['order_id'])) {
            return false;
        }
        // 处理逻辑
        $process_result = kernel::single("unicom_order_pushmessage")->deliveryOrder($msg['data']['order_id']);
        return $process_result['Result'] == true ? true : false;
    };

    $setRetry = array(
        'is_retry' => true,
        'delay_level' => MQ_RETRY_LEVEL_GENERAL
    );

    $amqp = new \Neigou\AMQP();
    $ret = $amqp->ConsumeMessage('unicom_order_delivery', 'service', 'order.delivery.success', $fun,$setRetry);

    var_dump($ret);
} catch (Exception $e) {
    echo 'Caught exception: ', $e->getMessage(), "\n";
}