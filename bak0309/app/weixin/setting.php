<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
$setting = array(
'ecos.leho.weixin.appid'=>array('type'=>SET_T_STR,'default'=>'wxf3249ddedebd8b4d','desc'=>app::get('sms189')->_('APP ID')),
'ecos.leho.weixin.appsecret'=>array('type'=>SET_T_STR,'default'=>'784df07d2aaa9cb6676df17f897c6e61','desc'=>app::get('sms189')->_('AppSecret'),'style'=>'width:270px;','helpinfo'=>'<span class=\'notice-inline\'>'.app::get('b2c')->_('上述Appid和AppSecret值请至微信公众平台（此配置为服务号或认证过的订阅号） 功能》高级功能》开发模式》开发者凭据 中复制粘贴过来').'</span>'),
);
