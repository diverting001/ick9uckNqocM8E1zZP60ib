<?php
/**
* Power By ShopEx Jxwinter
* Time  2012-04-10  NO.193
*
*/

function theme_widget_cat($setting,&$smarty){
	if(is_array($setting['cat_order'])) asort($setting['cat_order']);
	foreach ((array)$setting['cat_order'] as $_catid=>$_cat_order){
		$data[$_catid] = kernel::single('b2c_widgets_goods_cat')->getGoodsCatMap($_catid);    //通过数据接口取数据
		$data[$_catid] = $data[$_catid][$_catid];
	}

    return $data;
}
?>
