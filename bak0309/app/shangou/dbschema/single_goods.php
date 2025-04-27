<?php
$db['single_goods']=array (
		'columns' =>
		array (
				'item_id' => 
				array(
						'type' => 'bigint unsigned',
						'required' => true,
						'pkey' => true,
						'extra' => 'auto_increment',
						'label' => '活动ID',
						'width' => 110,
						'hidden' => true,
						'editable' => false,
						'in_list' => false,
				),
				'goods_id' =>
				array (						
						'type' => 'table:goods@b2c',
						'required' => true,
						'label' => '商品ID',
						'width' => 110,
						'hidden' => true,
						'editable' => false,
						'in_list' => false,
				),
				'bn' =>
				array(
						'type' => 'table:goods@b2c',
						'label' => app::get('b2c')->_('商品编号'),
					    'width' => 110,
					    'searchtype' => 'head',
					    'editable' => true,
					    'filtertype' => 'yes',
					    'filterdefault' => true,
					    'in_list' => true,
				),
				'name' => 
				array(
						'type' => 'table:goods@b2c',
						'required' => true,
						'label' => app::get('b2c')->_('商品名称'),
						'is_title' => true,
						'width' => 310,
						'searchtype' => 'has',
						'editable' => true,
						'filtertype' => 'custom',
						'filterdefault' => true,
						'filtercustom' =>
						array (
								'has' => app::get('b2c')->_('包含'),
								'tequal' => app::get('b2c')->_('等于'),
								'head' => app::get('b2c')->_('开头等于'),
								'foot' => app::get('b2c')->_('结尾等于'),
						),
						'in_list' => true,
						'default_in_list' => true,
						'order'=>'1',
				),
				
				's_time' =>
			    array (
			    	'type' => 'time',
			    	'label' =>  app::get('shangou')->_('活动开始时间'),
			    	'width' => 110,
			    	'editable' => false,
			    	'default_in_list' => true,
			    	'in_list' => true,
			    ),
			    'e_time' =>
			    array (
			    	'type' => 'time',
			    	'label' =>  app::get('shangou')->_('活动结束时间'),
			    	'width' => 110,
			    	'editable' => false,
			    	'default_in_list' => true,
			    	'in_list' => true,
			    ),
			    'last_modify' =>
			    array (
			      'type' => 'last_modify',
			      'label' => app::get('shangou')->_('更新时间'),
			      'width' => 110,
			      'editable' => false,
			      'in_list' => true,
			      'orderby' => true,
			    ),
				'd_order' =>
				array (
						'type' => 'number',
						'default' => 50,
						'required' => true,
						'label' => '排序',
						'width' => 110,
						'editable' => false,
				),

		),
		'engine' => 'innodb',
		'version' => '$Rev:  $',
		'comment' => app::get('b2c')->_('单品闪购表'),
);

?>