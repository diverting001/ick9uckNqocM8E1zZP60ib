<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

/*基础配置项*/
$setting['author']='tylerchao.sh@gmail.com';
$setting['version']='v1.0';
$setting['name']='极品尖货-挂件';
$setting['order']=0;
$setting['stime']='2015-01';
$setting['catalog']='商品相关';
$setting['description'] = '指定四个商品单品并上传相应的图片';
$setting['userinfo'] = '';
$setting['usual']    = '1';
$setting['vary'] ='';
$setting['tag']    = 'auto';
$setting['template'] = array(
                            'default.html'=>app::get('b2c')->_('默认')
                        );

?>
