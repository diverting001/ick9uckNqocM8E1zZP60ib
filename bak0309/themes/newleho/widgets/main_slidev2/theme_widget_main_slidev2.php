<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

function theme_widget_main_slidev2(&$setting,&$render){
    if($system->theme){
        $theme_dir = kernel::base_url().'/themes/'.$smarty->theme;
    }else{
        $theme_dir = kernel::base_url().'/themes/'.app::get('site')->getConf('current_theme');
    }
    if($setting['pic']){
        foreach($setting['pic'] as $key=>$value){
            if($value['link']){
                if($value["url"]){
                    $value["linktarget"]=$value["url"];
                }
                $setting['pic'][$key]['link'] = str_replace('%THEME%',$theme_dir,$value['link']);
                $setting['pic'][$key]['linkinfocontentarray'] = explode('|', $value['linkinfocontent']);
            }
        }
    }
    return $setting;
}
?>
