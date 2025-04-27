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
$setting['name']='首页闪购活动展示(异步加载版)';
$setting['stime']='2014-07';
$setting['catalog']='闪购活动相关';
$setting['description'] = '展示全部分类下的最新活动,即将结束的除外(具体即将结束时间范围配置，请在控制面板设置)';
$setting['usual']    = '0';
$setting['template'] = array(
                            'default.html'=>app::get('b2c')->_('默认')
                        );
?>
