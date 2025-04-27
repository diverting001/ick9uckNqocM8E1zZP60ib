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


$refundObj = kernel::single('ectools_newrefund');
$autoRefundApp = $refundObj->getAutoRefundPayAppId();
$payAppIdStr = '';
foreach ($autoRefundApp as $appkey) {
    $payAppIdStr .= "'{$appkey}',";
}

$db = kernel::database();
$refunds = app::get('ectools')->model('newrefunds');
$refundList = $db->select("select bill.rel_id as order_id , refund.payment_id, refund.t_begin, refund.refund_id , refund.money , refund.cur_money , refund.member_id , refund.pay_app_id, refund.pay_type, refund.trade_no from sdb_ectools_newrefunds refund left join sdb_ectools_order_refund_bills bill on refund.refund_id=bill.bill_id where refund.status='progress' and pay_app_id in (" . trim($payAppIdStr, ",") . ") limit 0,500");
if ($refundList) {
    foreach ($refundList as $refundInfo) {
        $msg = '处理成功';
        $status = $refundObj->getRefundStatus($refundInfo, $msg);
        echo "refundId:" . $refundInfo['refund_id'] . ",状态:" . $status . ",msg:" . $msg . "\r\n";
    }
}
$redisClient->delete($redisKey);
