<?php

echo __FILE__, '_START:', date('Y-m-d H:i:s'), PHP_EOL;

/**
 * 联通平台商品基础信息
 */
require(dirname(__FILE__) . '/config.php');

/* @var wmqy_goods $goods_lib */
$goods_lib = kernel::single('wmqy_goods');
/* @var wmqy_mdl_goods $goods_model */
$goods_model = app::get('wmqy')->model('goods');

// 获取待同步的数据
$sku_list = $goods_model->getSkusByStatus('checking');
foreach ($sku_list as $sku_info) {
    $result = $goods_lib->initGoods($sku_info, $msg);
    echo $sku_info['bn'] . ' => ' . $msg, PHP_EOL;
}

echo __FILE__, '_OVER:', date('Y-m-d H:i:s'), PHP_EOL;
