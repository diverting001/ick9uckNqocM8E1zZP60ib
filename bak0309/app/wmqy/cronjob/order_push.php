<?php
require(dirname(__FILE__) . '/config.php');
echo __FILE__, '_START:', date('Y-m-d H:i:s'), PHP_EOL;
/* @var wmqy_order $order_lib */
$order_lib = kernel::single('wmqy_order');

//$order_lib->push('201904121132156886');
//die;


/* @var wmqy_mdl_order $order_model */
$order_model = app::get('wmqy')->model('order');
// 获取待同步的数据
while (1) {
    $order_list = $order_model->getListByStatus('ready', 100);
    if (empty($order_list)) {
        echo '中粮推送订单结束', PHP_EOL;
        break;
    }
    foreach ($order_list as $order_info) {
        $result = $order_lib->push($order_info, $err_msg);
        echo $order_info['order_id'] . ' => ' . ($result ? 'ok' : 'fail') . ':' . $err_msg, PHP_EOL;
    }
}
echo __FILE__, '_OVER:', date('Y-m-d H:i:s'), PHP_EOL;