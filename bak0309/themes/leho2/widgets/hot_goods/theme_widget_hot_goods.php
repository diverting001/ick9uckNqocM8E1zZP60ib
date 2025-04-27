<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

function theme_widget_hot_goods(&$setting,&$render){
    $setting['yiren_image'] = base_storager::image_path($setting['yiren_image']);
    $setting['goods_image'] = base_storager::image_path($setting['goods_image']);
	return $setting;
}
?>






