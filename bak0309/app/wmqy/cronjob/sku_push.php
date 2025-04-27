<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<?php
/**
 * 联通平台商品基础信息
 */
require(dirname(__FILE__) . '/config.php');
echo __FILE__, '_START:', date('Y-m-d H:i:s'), PHP_EOL;
/* @var wmqy_goods $goods_lib */
$goods_lib = kernel::single('wmqy_goods');
/* @var wmqy_mdl_goods $goods_model */
$goods_model = app::get('wmqy')->model('goods');
// 获取待同步的数据
while (1) {
    $product_list = $goods_model->getSkusByStatus('ready', 100);
    if (empty($product_list)) {
        echo '中粮推送商品结束', PHP_EOL;
        break;
    }
    foreach ($product_list as $product_info) {
        $result = $goods_lib->pushSku($product_info);
        echo $product_info['bn'] . ' => ' . ($result ? 'ok' : 'fail'), PHP_EOL;
    }
}
echo __FILE__, '_OVER:', date('Y-m-d H:i:s'), PHP_EOL;