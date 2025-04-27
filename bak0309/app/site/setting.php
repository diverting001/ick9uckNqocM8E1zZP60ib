<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 

$setting = array(
    'base.site_params_separator'=>array('type'=>SET_T_ENUM, 'default'=>'-', 'required'=>true, 'options'=>array('-'=>'-'), 'desc'=>app::get('site')->_('URL参数分隔符')),
    'base.site_page_cache'=>array('type'=>SET_T_BOOL, 'default'=>'true', 'required'=>true, 'desc'=>app::get('site')->_('启用全页缓存')),
    'base.enable_site_uri_expanded'=>array('type'=>SET_T_BOOL, 'default'=>'true', 'required'=>true, 'desc'=>app::get('site')->_('启用扩展名'),'javascript'=>' var _site_uri_expanded_name = $$(\'input[name^=set[base.site_uri_expanded_name]\').getParent(\'tr\');
$$(\'input[name^=set[base.enable_site_uri_expanded]\').addEvent(\'click\',function(e){if(this.value==\'false\'){_site_uri_expanded_name.setStyle(\'display\',\'none\');}else {_site_uri_expanded_name.setStyle(\'display\',\'\');}});$$(\'input[name^=set[base.enable_site_uri_expanded]\').each(function(item){if(item.checked && item.value==\'false\')_site_uri_expanded_name.setStyle(\'display\',\'none\');});
    '),
    'base.site_uri_expanded_name'=>array('type'=>SET_T_STR, 'required'=>true, 'default'=>'html', 'desc'=>app::get('site')->_('URL扩展名(例:html)')),
    'base.check_uri_expanded_name'=>array('type'=>SET_T_BOOL, 'required'=>true, 'default'=>'true', 'desc'=>app::get('site')->_('启用URL扩展名检查')),
    'site.name'=>array('type'=>SET_T_STR, 'default'=>app::get('b2c')->_('点此设置您商店的名称'), 'desc'=>app::get('site')->_('站点名称')),
    'page.default_title'=>array('type'=>SET_T_STR, 'default'=>'', 'desc'=>app::get('site')->_('网页默认标题')),
    'page.default_keywords'=>array('type'=>SET_T_STR, 'default'=>'', 'desc'=>app::get('site')->_('网页默认关键字')),
    'page.default_description'=>array('type'=>SET_T_TXT, 'default'=>'', 'desc'=>app::get('site')->_('网页默认简介')),
    'system.foot_edit' => array('type'=>SET_T_HTML, 'desc'=>app::get('site')->_('网页底部信息'), 'default'=>'TODO(footer)'),
    'system.site_icp'=>array('type'=>SET_T_STR, 'default'=>'', 'desc'=>app::get('site')->_('备案号')),
    'errorpage'=>array('type'=>SET_T_STR, 'default'=>'404', 'desc'=>app::get('site')->_('404')),
);
