<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

function theme_widget_five_slide(&$setting,&$render){
    $goodsList = app::get('b2c')->model('goods')->getList('goods_id,name,mktprice,price,image_default_id',array('goods_id|in'=>$setting['goodsList']));
    foreach ($goodsList as &$row) {
        $row['dianx']  = $setting['dianx'][$row['goods_id']];
        $row['diany']  = $setting['diany'][$row['goods_id']];
        $row['goodsx'] = $setting['goodsx'][$row['goods_id']];
        $row['goodsy'] = $setting['goodsy'][$row['goods_id']];
        $row['goods_image_url'] = base_storager::image_path($row['image_default_id']);
    }
    $setting['showGoods'] = $goodsList;
    $setting['image_url'] = base_storager::image_path($setting['image']);
    $render->pagedata['data'] = $setting;
    #echo('<pre>');print_r($setting);;
    return $setting;
}
?>
