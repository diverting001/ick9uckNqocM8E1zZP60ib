<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
$setting = array(
	'shangou.event.ending.time'=>array('type'=>SET_T_INT,'default'=>'24','desc'=>app::get('b2c')->_('默认剩余多少时间内的活动为即将结束的活动'),'helpinfo'=>'<span class=\'notice-inline\'>'.app::get('b2c')->_('单位(小时)').'</span>','javascript'=>'$$("input[name^=set[shangou.event.ending.time]]").addEvent("change",function(e){var _target = $(e.target)||$(e);if (_target.value == "" || _target.value == "0")_target.value = "24";});','vtype'=>'digits'),
);
