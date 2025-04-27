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
$setting['name']='首页发现频道单品商品展示';
$setting['order']=0;
$setting['stime']='2014-11';
$setting['catalog']='商品相关';
$setting['description'] = '展示全部单品商品列表中的商品';
$setting['userinfo'] = '';
$setting['usual']    = '0';
$setting['template'] = array(
                            'default.html'=>app::get('b2c')->_('默认')
                        );
?>