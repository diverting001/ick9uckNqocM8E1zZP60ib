<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
/*基础配置项*/
$setting['author']='senpan@vip.qq.com';
$setting['version']='v1.0';
$setting['name']='页头的暗号区域';
$setting['order']=0;
$setting['stime']='2015-03';
$setting['catalog']='会员相关';
$setting['description'] = '异步获取当前会员使用的暗号';
$setting['userinfo'] = '';
$setting['usual']    = '0';
$setting['template'] = array(
                            'default.html'=>app::get('b2c')->_('默认')
                        );
?>