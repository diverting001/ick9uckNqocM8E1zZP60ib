<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

function theme_widget_events_category(&$setting,&$render){
	$mdl_event = app::get('shangou')->model('events');
    $cat_mdl = app::get('shangou')->model('cat');
    $cat_list =$cat_mdl->get_cat_list();
    $tree = array();
    $catids = array();
    foreach ($cat_list as $cat){
    	if ($cat['pid'] == 0) {
    		$tree[$cat['cat_id']] = $cat;
    		$catids[$cat['cat_id']][] = intval($cat['cat_id']);
    	}
    	if ($cat['pid'] != 0) {
    		$tree[$cat['pid']]['child'][] = $cat;
    		$catids[$cat['pid']][] = intval($cat['cat_id']);
    	}
    }
    
    $time = time();
    $basefilter = array('evt_type'=>'show','s_time|lthan'=>$time,'e_time|than'=>$time);
 
    foreach ($catids as $key=>$item){
    	$filter = array_merge($basefilter,array('cat_id'=>$item));
    	$data = $mdl_event->getList('*',$filter,0,8);
    	$tree[$key]['events'] = $data;
    }
	unset($catids);
    return $tree;

}

?>






