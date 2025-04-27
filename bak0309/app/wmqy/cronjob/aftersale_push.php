<?php
require(dirname(__FILE__) . '/config.php');
echo __FILE__, '_START:', date('Y-m-d H:i:s'), PHP_EOL;
/* @var wmqy_aftersale $lib */
$lib = kernel::single('wmqy_aftersale');
/* @var wmqy_mdl_aftersale $model */
$model = app::get('wmqy')->model('aftersale');
// 获取待同步的数据
while (1) {
    $list = $model->getListByStatus('ready', 100);
    if (empty($list)) {
        echo '中粮推送售后单结束', PHP_EOL;
        break;
    }
    foreach ($list as $info) {
        $result = $lib->push($info, $err_msg);
        echo $info['after_sale_bn'] . ' => ' . ($result ? 'ok' : 'fail') . ':' . $err_msg, PHP_EOL;
    }
}
echo __FILE__, '_OVER:', date('Y-m-d H:i:s'), PHP_EOL;