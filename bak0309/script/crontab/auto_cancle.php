<?php
/**
 * @desc 自动取消订单脚本
 * @author daifei<daifei@shopex.cn>
 */
@require_once(realpath(dirname(__FILE__)).'/../../config/config.php');
define('APP_DIR',ROOT_DIR."/app/");
include_once(APP_DIR."/base/defined.php");

require_once(ROOT_DIR.'/app/base/kernel.php');
if(!kernel::register_autoload()){
    require_once(APP_DIR.'/base/autoload.php');
}

date_default_timezone_set(
    defined('DEFAULT_TIMEZONE') ? ('Etc/GMT'.(DEFAULT_TIMEZONE>=0?(DEFAULT_TIMEZONE*-1):'+'.(DEFAULT_TIMEZONE*-1))):'UTC'
);

$obj_order_cancel = kernel::single('b2c_order_cancel');
$obj_order = app::get('b2c')->model('orders');

$_time = time() - intval(app::get('b2c')->getConf('site.order.cancel.time'))*60;
$_arr = $obj_order->getList('order_id',array('createtime|lthan'=>$_time, 'status'=>'active', 'pay_status'=>'0'));

$obj_controller = app::get('b2c')->controller('site_product');
foreach ($_arr as $_item){
	$sdf['order_id'] = $_item['order_id'];
	$sdf['op_id'] = 1;
	$sdf['opname'] = 'admin';
	$sdf['account_type'] = 'shopadmin';

    $obj_order_cancel->generate($sdf, $obj_controller, $message);
}