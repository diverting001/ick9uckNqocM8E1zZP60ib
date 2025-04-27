<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

function theme_widget_index_faxian(&$setting,&$render){
    
    $obj_goods_single = app::get('b2c')->model('goods_single');
    $obj_goods =app::get('b2c')->model('goods');
	//获取
   $filter = array('sshow'=>'show','platform'=>array('pc','both'));
	$data = $obj_goods_single->getList('*',$filter);
	
	if ( isset( $_COOKIE['MLV'] ) ) {
		if(!cachemgr::get('member_lv_disCount'.$_COOKIE['MLV'],$dis_count)){
			cachemgr::co_start();
			$member_level = $_COOKIE['MLV'];
			(array)$arr = app::get('b2c')->model('member_lv')->getList('dis_count', array('member_lv_id' => $member_level));
			$dis_count = $arr[0]['dis_count'];
			cachemgr::set('member_lv_disCount'.$_COOKIE['MLV'], $dis_count, cachemgr::co_end());
		}
	}
	$objLvprice     = app::get('b2c')->model('goods_lv_price');
	$objProduct     = app::get('b2c')->model('products');
	$gids = array();
	$result = array();
	$order = array();
	foreach ($data as &$item){
		$_goods = $obj_goods->dump(array('goods_id'=>$item['goods_id']),'name,price,mktprice');
		$item['name'] = $_goods['name'];
		$item['price'] = $_goods['price'];
		$item['mktprice'] = $_goods['mktprice'];
		$lv_price = $objLvprice->getList('price',array('goods_id'=>$item['goods_id'],'level_id'=>$member_level));
		if ( isset( $dis_count ) ) {
			if(count($lv_price) > 0){
				$lv_price = end($lv_price);
				$item['memprice'] = $lv_price['price'];
			}else{
				$item['memprice'] = $item['price'] * $dis_count;
			}
		}else{
			$item['memprice'] =  $item['price'];
		}
		
		$result[$item['goods_id']] = $item;
		$gids[] = $item['goods_id'];
		$order[$item['goods_id']] = intval($item['sorder']);
	}
	
	$rs = array();
	if($gids){
		$productData = $objProduct->getList('goods_id,product_id,marketable',array('goods_id'=>$gids,'is_default'=>'true'));
		foreach($productData as $k=>$val){
			$rs[$val['goods_id']] = $result[$val['goods_id']];
			$url_params = array('app'=>'b2c','ctl'=>'site_product','act'=>'index','args'=>array($val['product_id']));
			$rs[$val['goods_id']]['goodsLink'] = app::get('site')->router()->gen_url($url_params);
		}
	}
	unset($gids,$result);
	array_multisort($order,SORT_DESC,$rs);
	
    return $rs;
}
?>
