<?php
$db['events']=array (
  'columns' =>
  array (
    'evt_id' =>
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => '商品ID',
      'width' => 110,
      'hidden' => true,
      'editable' => false,
      'in_list' => false,
    ),
    'evt_name' =>
    array (
      'type' => 'varchar(200)',
      'required' => true,
      'default' => '',
      'label' => app::get('shangou')->_('活动名称'),
      'is_title' => true,
      'width' => 310,
      'searchtype' => 'has',
      'editable' => true,
      'filtertype' => 'custom',
      'filterdefault' => true,
      'filtercustom' =>
      array (
        'has' => app::get('shangou')->_('包含'),
        'tequal' => app::get('shangou')->_('等于'),
        'head' => app::get('shangou')->_('开头等于'),
        'foot' => app::get('shangou')->_('结尾等于'),
      ),
      'in_list' => true,
      'default_in_list' => true,
      'order'=>'1',
    ),
     'cat_id' =>
    array (
      'type' => 'table:cat',
      'required' => true,
      'default' => 0,
      'label' => app::get('shangou')->_('频道'),
      'width' => 75,
      'editable' => true,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'orderby'=>true,
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
    'p_order' =>
    array (
      'type' => 'number',
      'default' => 30,
      'required' => true,
      'label' => app::get('shangou')->_('排序'),
      'width' => 110,
      'editable' => false,
      'hidden' => true,
      'in_list' => false,
      'orderby'=>true,
    ),
 	'intro' =>
    array (
      'type' => 'longtext',
      'label' => app::get('shangou')->_('介绍'),
      'width' => 110,
      'hidden' => true,
      'editable' => false,
      'filtertype' => 'normal',
    ),
    'evt_type' =>
    array (
      'type' =>
      array (
        'noshow' => app::get('shangou')->_('暂不显示'),
        'show' => app::get('shangou')->_('预告显示'),
      ),
      'default' => 'noshow',
      'required' => true,
      'label' => app::get('shangou')->_('前端显示状态'),
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
  	'act_type' =>
  	array (
  		'type' =>
  		array (
  			'normal' => app::get('shangou')->_('普通活动'),
  			'company' => app::get('shangou')->_('企业专场'),
  		),
  		'default' => 'normal',
  		'required' => true,
  		'label' => app::get('shangou')->_('活动类型'),
  		'width' => 100,
  		'editable' => false,
  		'in_list' => true,
  	),
  	'leho_type' =>
  	array (
  		'type' => 'bool',
  		'default' => 'false',
  		'required' => true,
  		'label' => app::get('shangou')->_('原产地直供频道'),
  		'width' => 110,
  		'editable' => false,
  		'in_list' => true,
  	),
  	'index_show' =>
  	array (
  		'type' => 'bool',
  		'default' => 'false',
  		'required' => true,
  		'label' => app::get('shangou')->_('首页推荐'),
  		'width' => 110,
  		'editable' => false,
  		'in_list' => true,
  	),
    'evt_logo' =>
    array (
      'type' => 'varchar(32)',
      'label' => app::get('shangou')->_('活动头图'),
      'width' => 110,
      'hidden' => true,
      'editable' => false,
      'in_list' => false,
    ),
  	'enevt_template' =>
  	array (
  		'type' => 'varchar(150)',
  		'label' => app::get('shangou')->_('PC端活动模版'),
  		'hidden' => true,
  		'editable' => false,
  	),
  	'wap_enevt_template' =>
  	array (
  		'type' => 'varchar(150)',
  		'label' => app::get('shangou')->_('WAP端活动模版'),
  		'hidden' => true,
  		'editable' => false,
  	),
    'evt_setting' =>
    array(
        'type' => 'serialize',
        'label' => app::get('shangou')->_('活动设置'),
        'deny_export' => true,
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'required' => true,
      'editable' => false,
    ),
    'like_count' =>
    array (
      'type' => 'int unsigned',
      'default' => 0,
      'required' => true,
      'editable' => false,
      'comment' => app::get('shangou')->_('喜欢次数'),
    ),    
    'view_count' =>
    array (
      'type' => 'int unsigned',
      'default' => 0,
      'required' => true,
      'editable' => false,
      'comment' => app::get('shangou')->_('浏览次数'),
    ),
  ),
  'comment' => app::get('shangou')->_('闪购活动表'),
  'index' =>
  array (
    'idx_p_order' =>
    array(
        'columns' =>
        array (
            0 => 'p_order',
            ),
        ),
      ),
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
  'comment' => app::get('shangou')->_('闪购活动表'),
);
