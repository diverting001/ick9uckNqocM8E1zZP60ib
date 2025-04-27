<?php
require(dirname(__FILE__) . '/config.php');
echo __FILE__, '_START:', date('Y-m-d H:i:s'), PHP_EOL;
try {
    $fun = function ($msg) {
        if (empty($msg['data']['after_sale_bn'])) {
            return false;
        }
        /* @var wmqy_aftersale $lib */
        $lib = kernel::single('wmqy_aftersale');
        return $lib->init($msg['data']['after_sale_bn']);
    };
    $setRetry = array(
        'is_retry' => true,
        'delay_level' => MQ_RETRY_LEVEL_GENERAL
    );
    $amqp = new \Neigou\AMQP();
    $ret = $amqp->ConsumeMessage('wmqy.order.aftersale.update', 'service', 'aftersale.status.update', $fun);
    var_dump($ret);
} catch (Exception $e) {
    echo 'Caught exception: ', $e->getMessage(), "\n";
}
echo __FILE__, '_OVER:', date('Y-m-d H:i:s'), PHP_EOL;