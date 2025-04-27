<?php
/**
 * @desc O2O订单支付完成发送券码
 */
require(dirname(__FILE__) . '/config.php');

try {
    $amqp = new \Neigou\AMQP();
    $amqp->ConsumeMessage('ec_o2o_order_payed','service','order.confirm.success', function ($msg) {
        \Neigou\Logger::General("ec_o2o_order_payed_monitor",
            array('action' => 'consumer_start', 'data' => $msg));

        if (!isset($msg['data']['order_id']) || empty($msg['data']['order_id'])) {
            return false;
        }

        $ret = \Neigou\ApiClient::doServiceCall('order', 'Order/Get', 'v1', null, array('order_id' => $msg['data']['order_id']));
        \Neigou\Logger::General("ec_o2o_order_payed_monitor",
            array('action' => 'consumer_order_get_api', 'data' => $ret));

        $order_info = array();
        if((!empty($ret['service_data']['error_code']) && 'SUCCESS' == $ret['service_data']['error_code'])
            && !empty($ret['service_data']['data']))
        {
            $order_info = $ret['service_data']['data'];
        }

        \Neigou\Logger::General("ec_o2o_order_payed_monitor",
            array('action' => 'consumer_get_order_info', 'data' => $order_info));

        $order_confirm_finished = kernel::single("b2c_service_orderconfirmfinished");
        $result = $order_confirm_finished->o2o_order_pay_finish($order_info);
        \Neigou\Logger::General("ec_o2o_order_payed_monitor",
            array('action' => 'consumer_result', 'data' => array('result' => !empty($result) ? 'true' : 'false')));

        echo $msg['data']['order_id'].PHP_EOL;
        return true;
    });
} catch (\Exception $e) {
    /**  MQ 等待5秒后无消息，抛出异常不做处理  */
}