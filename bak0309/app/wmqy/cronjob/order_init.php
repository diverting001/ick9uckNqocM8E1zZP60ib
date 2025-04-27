<?php
echo __FILE__, '_START:', date('Y-m-d H:i:s'), PHP_EOL;
require(dirname(__FILE__) . '/config.php');
try {
    $fun = function ($msg) {
        if (!isset($msg['data']['order_id']) || empty($msg['data']['order_id'])) {
            return false;
        }
        // 处理逻辑
        return kernel::single('wmqy_order')->initOrder($msg['data']['order_id']);
    };

    $setRetry = array(
        'is_retry' => true,
        'delay_level' => MQ_RETRY_LEVEL_GENERAL
    );

    $amqp = new \Neigou\AMQP();
    $ret = $amqp->ConsumeMessage('wmqy.order.finish.success', 'service', 'order.finish.success', $fun, $setRetry);
    var_dump($ret);

} catch (Exception $e) {
    echo 'Caught exception: ', $e->getMessage(), "\n";
}
echo __FILE__, '_OVER:', date('Y-m-d H:i:s'), PHP_EOL;