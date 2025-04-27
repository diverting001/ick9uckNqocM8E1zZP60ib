<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

function theme_widget_tip_goods(&$setting,&$render){
	
	//$mdl_product = app::get('b2c')->model('products');
	foreach ($setting['goods']  as &$item){
		//$tmp_gid = $mdl_product->getRow('goods_id',array('product_id'=>$item['gid']));
		$item['url'] = app::get('site')->router()->gen_url(array('app'=>'b2c', 'ctl'=>'site_product', 'arg0'=>$item['gid'],'full'=>1));
	}
    return $setting;

}


?>






