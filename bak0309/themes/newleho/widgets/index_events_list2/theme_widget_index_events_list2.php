<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

function theme_widget_index_events_list2(&$setting,&$render){
    
   $data['url']=  app::get('site')->router()->gen_url( array('app'=>'shangou', 'ctl'=>'site_events', 'act'=>'eventslist' ) );
   return $data;
}
?>
