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
$setting['name']='闪购活动导航分类(异步版)';
$setting['order']=0;
$setting['stime']='2014-011';
$setting['catalog']='闪购活动相关';
$setting['description'] = '异步获取数据，支持二级分类展示；支持关联闪购活动';
$setting['usual']    = '0';
$setting['template'] = array(
                            'default.html'=>app::get('b2c')->_('默认')
                        );
?>
