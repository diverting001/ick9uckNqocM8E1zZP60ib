<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

function theme_widget_index_events_list(&$setting,&$render){
    
    $mdl_event = app::get('shangou')->model('events');
	
	//获取
	$endingtime = app::get('shangou')->getConf('shangou.event.ending.time');
	$endingtime = empty($endingtime) ? 24 : $endingtime;
	$time = time();

	$etime = $time + $endingtime*60*60;
	
	$filter = array('leho_type'=>'false','act_type'=>'normal','evt_type'=>'show','s_time|lthan'=>$time,'e_time|than'=>$etime);
	$data = $mdl_event->getList('*',$filter,0,10);
    return $data;
}
?>
