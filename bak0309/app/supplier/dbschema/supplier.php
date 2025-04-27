<?php
$db['supplier']=array (
  'columns' =>
  array (
    'sp_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
    ),
    'sp_name' =>
    array (
      'type' => 'varchar(255)',
      'required' => true,
      'in_list' => true,
      'default_in_list' => true,
	  'searchtype' => 'has',
   	  'editable' => false,
   	  'filtertype' => 'custom',
   	  'filterdefalut' => true,
   	  'filtercustom' =>
   	   array (
   			'has' => '包含',
   			'tequal' => '等于',
			'head' => '开头等于',
   			'foot' => '结尾等于',
    	),
      'width' => 300,
      'label' => '供货商名称',
      'order' => '1',
    ),
 'sp_bn' =>
 	array (
 		'type' => 'varchar(100)',
 		'required' => true,
 		'in_list' => true,
 		'searchtype'=>'head',
 		'filtertype' => 'yes',
 		'filterdefault' => true,
 		'default_in_list' => true,
 		'width' => 100,
 		'label' => '供货商编码',
 		'order' => '2',
  	),
  	'co_type' =>
  	array (
  		'type' =>
  		array (
  			'nego' => '洽谈',
  			'cooper' => '合作',
  			'stop' => '停止合作',
  		),
  		'width' => 100,
  		'label' => '合作状态',
  		'in_list' => true,
  		'default_in_list' => true,
  		'order' => '3',
  		'filtertype' => 'yes',
  		'filterdefault' => true,
  	),
  	'co_mode' =>
  	array (
  		'type' =>
  		array (
  			'tosell' => '经销',
  			'onsell' => '代销',
  	   	    'joint' => '联营',
  		),
  		'width' => 100,
  		'label' => '合作模式',
  		'in_list' => true,
  		'default_in_list' => true,
  		'order' => '4',
  		'filtertype' => 'yes',
  		'filterdefault' => true,
  	),
  	'co_mode_s' =>
  	array (
  		'type' => 'time',
  		'label' => '合作开始时间',
  	),
   'co_mode_e' =>
 	array (
 		'type' => 'time',
 		'label' => '合作结束时间',
  	),
    'operater' =>
    array (
      'type' => 'varchar(255)',//'table:users@desktop',
      'required' => true,
      'label' => '商务负责人',
      'width' => 100,
      'in_list' => true,
      'default_in_list' => true,
      'order' => '5',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'operater_s' =>
    array (
      'type' => 'time',
      'label' => '商务负责开始时间',
    ),
    'operater_e' =>
    array (
      'type' => 'time',
      'label' => '商务负责结束时间',
    ),
  	'pay_type' =>
  	array (
  		'type' => array(
  			'cash' => '现款',
  			'term' => '账期',
  		),
  		'required' => true,
  		'default' => 'cash',
  		'label' => '付款方式',
  		'in_list' => true,
  		'default_in_list' => true,
  		'order' => '11',
  		'filtertype' => 'yes',
  		'filterdefault' => true,
  	),
  	'terminfo' =>
  	array (
  		'type' => 'varchar(100)',
  		'required' => true,
  		'default' => '',
  		'label' => '账期信息',
  	),
  	'goods_type' =>
  	array (
  		'type' =>
  		array (
  			'c' => '服装',
  			'a' => '配饰',
  			's' => '鞋',
  			'h' => '家居',
  			'b' => '美妆',
  			'f' => '食品',
  			'e' => '电子产品',
  			'o' => '其它',
  		),
  		'width' => 100,
  		'label' => '商品类别',
  		'in_list' => true,
  		'default_in_list' => true,
  		'order' => '6',
  		'filtertype' => 'yes',
  		'filterdefault' => true,
  	),
  	'rebate' =>
  	array (
  		'type' => 'varchar(100)',
  		'in_list' => true,
  		'width' => 100,
  		'label' => '折扣',
  		'order' => '7',
  	),
  	'sp_contact' =>
  	array (
  		'type' => 'varchar(100)',
  		'required' => true,
  		'in_list' => true,
  		'filtertype' => 'yes',
  		'filterdefault' => true,
  		'default_in_list' => true,
  		'width' => 100,
  		'label' => '联系人',
  		'order' => '8',
  	),
  	'sp_phone' =>
  	array (
  		'type' => 'varchar(100)',
  		'required' => true,
  		'in_list' => true,
  		'filtertype' => 'yes',
  		'filterdefault' => true,
  		'default_in_list' => true,
  		'width' => 100,
  		'label' => '联系电话',
  		'order' => '9',
  	),
  	'sp_email' =>
  	array (
  		'type' => 'varchar(100)',
  		'in_list' => true,
  		'default' => '',
  		'filtertype' => 'yes',
  		'filterdefault' => true,
  		'width' => 100,
  		'label' => '联系邮箱',
  		'order' => '10',
  	),
  	'sp_addr' =>
  	array (
  		'type' => 'varchar(100)',
  		'required' => true,
  		'in_list' => true,
  		'filtertype' => 'yes',
  		'filterdefault' => true,
  		'default_in_list' => true,
  		'width' => 100,
  		'label' => '公司地址',
  		'order' => '10',
  	),
  	'invoice_type' =>
  	array (
  		'type' => array(
  			'normal' => '普通发票',
  			'increment' => '增值税发票',
  		),
  		'required' => true,
  		'default' => 'normal',
  		'label' => '发票类型',
  		'in_list' => true,
  		'default_in_list' => true,
  		'order' => '11',
  		'filtertype' => 'yes',
  		'filterdefault' => true,
  	),
  	'title' =>
  	array (
  		'type' => 'varchar(50)',
  		'label' => '发票抬头',
  		'comment' => '发票抬头(个人或单位名称)',
  	),
  	'invoice_tel' =>
  	array (
  		'type' => 'varchar(20)',
  		'label' => '公司电话',
  	),
  	'identify_num' =>
  	array (
  		'type' => 'varchar(32)',
  		'label' => '纳税人识别号',
  	),
  	'bank_name' =>
  	array (
  		'type' => 'varchar(32)',
  		'label' => '开户银行名称',
  	),
  	'bank_account' =>
  	array (
  		'type' => 'varchar(50)',
  		'label' => '开户银行帐号',
  	),
  	'reg_addr' =>
  	array (
  		'type' => 'varchar(60)',
  		'label' => '注册地址',
  	),
  	'remark' =>
  	array (
  		'type' => 'longtext',
  		'comment' => '备注',
  		'editable' => false,
  		'label' => '备注',
  	),
  	'last_modify' =>
  	array (
  		'type' => 'last_modify',
  		'label' => '更新时间',
  		'width' => 110,
  		'editable' => false,
  		'in_list' => true,
  		'default_in_list' => true,
  		'order' => '6',
  	),
  	'disabled' =>
  	array (
  		'type' => 'bool',
  		'default' => 'false',
  		'required' => true,
  		'editable' => false,
  	),
  ),
  'comment' => '供应商表',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);