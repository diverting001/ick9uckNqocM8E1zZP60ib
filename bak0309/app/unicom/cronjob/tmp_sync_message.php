<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<?php
/**
 *   同步删除消息
 */
require(dirname(__FILE__) . '/config.php');

echo 'UNICOM DELETE MESSAGE START';


$errMsg = '';
$msgIdArr = explode(',', '1');
$result = kernel::single("unicom_order_handle")->delUnicomPushMessage(array('ErrorId' => 10000), $msgIdArr);

echo '<PRE>';print_r($result);

echo 'UNICOM DELETE MESSAGE END';
