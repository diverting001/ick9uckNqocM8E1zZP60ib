<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

function theme_widget_index_anhao(&$setting,&$render){
	$data['url']=  app::get('site')->router()->gen_url( array('app'=>'shangou', 'ctl'=>'site_events', 'act'=>'faxian' ) );
	return $data;
}
?>
