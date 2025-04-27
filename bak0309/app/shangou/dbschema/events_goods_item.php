<?php
$db['events_goods_item']=array (
  'columns' => 
  array (
    'egi_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
      'width' => 110,
      'editable' => false,
    ),
    'evt_id' => 
    array (
      'type' => 'table:events',
      'default' => 0,
      'required' => true,
      'label' => '活动Event',
      'width' => 110,
      'editable' => false,
    ),
    'goods_id' => 
    array (
      'type' => 'table:goods@b2c',
      'default' => 0,
      'required' => true,
      'label' => '商品名称',
      'width' => 110,
      'editable' => false,
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
);