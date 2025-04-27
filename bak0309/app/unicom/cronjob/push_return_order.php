<?php

/**
 * 退货处理结果推送
 * @var [type]
 */
require(dirname(__FILE__) . '/config.php');

/*
  $after_sale_bn = '201807311825235';
  $order_info = kernel::single("unicom_order_pushmessage")->pushReturnOrder($after_sale_bn);
  var_dump($order_info);die;
 */

try {
    $fun = function ($msg) {
        if (!isset($msg['data']['after_sale_bn']) || empty($msg['data']['after_sale_bn'])) {
            return false;
        }

        $process_result = kernel::single("unicom_order_pushmessage")->pushReturnOrder($msg['data']['after_sale_bn']);
        return $process_result['Result'] == true ? true : false;
    };

    $setRetry = array(
        'is_retry' => true,
        'delay_level' => MQ_RETRY_LEVEL_GENERAL
    );

    $amqp = new \Neigou\AMQP();
    $ret = $amqp->ConsumeMessage('unicom_order_aftersale_status', 'service', 'aftersale.status.update', $fun,$setRetry);

    var_dump($ret);
} catch (Exception $e) {
    echo 'Caught exception: ', $e->getMessage(), "\n";
}