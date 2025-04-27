<?php
/**
 * @desc 自动清楚售后图片
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

$obj_return_image = app::get('image')->model('tmp_images');
$_arr_images = $obj_return_image->getList('*',array('expirestime|lthan'=>time(), 'status'=>'active', 'pay_status'=>'0'));

foreach ($_arr_images as $_arr){
	@unlink(ROOT_DIR.'/'.$_arr['file_dir']);
	
	$obj_return_image->delete(array('image_id'=>$_arr['image_id']));
}