<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

function theme_widget_item_info(&$setting,&$render){
         $limit = ($setting['limit'])?$setting['limit']:6;
	     $brand_list = app::get('b2c')->model('brand')->getList('*',array(),0,$limit,'ordernum desc');
         $setting['yiren_image'] = base_storager::image_path($setting['yiren_image']);
         $setting['goods_image'] = base_storager::image_path($setting['goods_image']);
          #echo('<pre>');print_r($setting);;
	return $brand_list;
}
?>






