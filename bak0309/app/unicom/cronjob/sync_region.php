<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<?php
/**
 *   同步联通地址信息
 */
require(dirname(__FILE__) . '/config.php');

echo 'UNICOM PULL REGION START';

echo kernel::single("unicom_cron_region")->pullRegion();

echo 'UNICOM PULL REGION END';