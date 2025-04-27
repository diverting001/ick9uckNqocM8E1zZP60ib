<?php

/**
 * 重新提交退货申请
 * 
 */

require(dirname(__FILE__) . '/config.php');

kernel::single("unicom_order_handle")->temp_apply_return_order();


