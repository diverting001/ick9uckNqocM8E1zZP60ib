<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
$setting = array(
'ecos.leho.sms189.appid'=>array('type'=>SET_T_STR,'default'=>'392135610000043184','desc'=>app::get('sms189')->_('应用ID')),
'ecos.leho.sms189.template_id'=>array('type'=>SET_T_STR,'default'=>'91005518','desc'=>app::get('sms189')->_('验证码短信模版ID')),
'ecos.leho.sms189.appsecret'=>array('type'=>SET_T_STR,'default'=>'69dccb6d5fc6264375b7ca13fea5e282','desc'=>app::get('sms189')->_('应用密钥'),'style'=>'width:270px;'),
'sms189.app.leho.api.test.status'=>array('type'=>SET_T_BOOL,'default'=>'false','desc'=>app::get('b2c')->_('是否开启测试模式'),'helpinfo'=>'<span class=\'notice-inline\'>'.app::get('b2c')->_('开启后支持POSTMAN进行测试').'</span>'),
'sms189.app.leho.api.test.value'=>array('type'=>SET_T_STR,'default'=>'LEHO_YES_API_TEST','desc'=>app::get('mobileapi')->_('api_test值'),'helpinfo'=>'<span class=\'notice-inline\'>'.app::get('b2c')->_('开启测试模式后，本字段不能为空;若为空，测试模式不会生效').'</span>'),
'ecos.leho.sms189.tokeninfo'=>array('type'=>SET_T_STR,'default'=>'','desc'=>app::get('sms189')->_('token')),


);
