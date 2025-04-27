<?php

/**
 * @desc 同步退款状态
 * @author daifei<daifei@shopex.cn>
 */
require_once(dirname(__FILE__) . '/../../config/config.php');
include_once(APP_DIR . "/base/defined.php");
#内购独有配置
require('./config/neigou_config.php');
//公用curl请求类
include './NG_PHP_LIB/Curl.php';
//公用loger类
include './NG_PHP_LIB/Logger.php';
//https类
include './NG_PHP_LIB/UrlProtocalConverter.php';
//curl客户端
include './NG_PHP_LIB/ApiClient.php';
//客户端配置
include './NG_PHP_LIB/Config.php';
//消息队列
include './NG_PHP_LIB/AMQP.php';

//Redis队列
include './NG_PHP_LIB/RedisClient.php';

require_once(ROOT_DIR . '/app/base/kernel.php');
if (!kernel::register_autoload()) {
    require_once(APP_DIR . '/base/autoload.php');
}

date_default_timezone_set(
        defined('DEFAULT_TIMEZONE') ? ('Etc/GMT' . (DEFAULT_TIMEZONE >= 0 ? (DEFAULT_TIMEZONE * -1) : '+' . (DEFAULT_TIMEZONE * -1))) : 'UTC'
);


$redisClient = kernel::single('base_kvstore_redis', "cron");
$redisKey = "sync_refund_status";
$lastTime = '';
$redisClient->fetch($redisKey, $lastTime);
if ($lastTime) {
    echo "脚本执行中...\r\n";
    return false;
}
$redisClient->store($redisKey, time(), 3600);
$db = kernel::database();
$refunds = app::get('ectools')->model('newrefunds');
$refundList = $db->select("select bill.rel_id as order_id , refund.refund_id , refund.money, refund.t_payed, refund.member_id , refund.pay_app_id, refund.pay_type, refund.trade_no from sdb_ectools_newrefunds refund left join sdb_ectools_order_refund_bills bill on refund.refund_id=bill.bill_id where refund.status in ('succ' , 'refusing') and (refund.message_status is null or refund.message_status = 0) limit 0,500");
if ($refundList) {
    foreach ($refundList as $refundInfo) {
        $sendData = array(
            'order_id' => $refundInfo['order_id'],
            'refund_name' => $refundInfo['pay_app_id'],
            'trade_no' => $refundInfo['trade_no'],
            'refund_time' => $refundInfo['t_payed'],
            'refund_id' => $refundInfo['refund_id'],
            'payment_system' => 'EC',
            "pay_money" => $refundInfo['money']
        );
        $ret = \Neigou\ApiClient::doServiceCall('order', 'Order/RefundConfirm', 'v1', null, $sendData, array());
        if ('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code']) {
            $sqlRes = $db->exec("update sdb_ectools_newrefunds set message_status=1 where refund_id='" . $refundInfo['refund_id'] . "'");
            if (isset($sqlRes['rs']) && $sqlRes['rs']) {
                echo "refundId:" . $refundInfo['refund_id'] . ",发送成功！\r\n";
            } else {
                \Neigou\Logger::Debug("ectools_newrefund.dorefund", array("sparam1" => json_encode($sendData), "sparam2" => json_encode($ret), "sparam3" => json_encode($sqlRes)));
                echo "refundId:" . $refundInfo['refund_id'] . ",发送失败！\r\n";
            }
        } else {
            $db->exec("update sdb_ectools_newrefunds set message_status=2 where refund_id='" . $refundInfo['refund_id'] . "'");
            \Neigou\Logger::Debug("ectools_newrefund.dorefund", array("sparam1" => json_encode($sendData), "sparam2" => json_encode($ret), "sparam3" => json_encode($sqlRes)));
            echo "refundId:" . $refundInfo['refund_id'] . ",调用失败！ret:" . json_encode($ret) . "\r\n";
        }
    }
}
$redisClient->delete($redisKey);
