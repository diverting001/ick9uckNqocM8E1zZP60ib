<?php
/**
 * 联通平台商品基础信息
 */
require(dirname(__FILE__) . '/config.php');
echo __FILE__, '_START:', date('Y-m-d H:i:s'), PHP_EOL;
$config_model = app::get('b2c')->model('config');
$mall_ids = $config_model->getConfigVal('wmqy_goods_mall_ids', 'neigou');
/* @var wmqy_goods $goods_lib */
$goods_lib = kernel::single('wmqy_goods');
/* @var wmqy_mdl_goods $goods_model */
$goods_model = app::get('wmqy')->model('goods');
$mall_id_arr = explode(',', $mall_ids);
foreach ($mall_id_arr as $mall_id) {
    $max_id = $config_model->getConfigVal('wmqy_module_mall_goods_max_id_' . $mall_id, 'neigou');
    $goods_model->initReadySku($mall_id, $max_id);
}
echo __FILE__, '_OVER:', date('Y-m-d H:i:s'), PHP_EOL;