<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<?php
/**
 *   同步联通商品分类
 */
require(dirname(__FILE__) . '/config.php');

echo 'UNICOM PULL GOODS CATEGORY START';

echo kernel::single("unicom_cron_goods")->pullGoodsCategory();

echo 'UNICOM PULL GOODS CATEGORY END';