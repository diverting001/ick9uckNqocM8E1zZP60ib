<?php
$db['events_lv']=array (
  'columns' =>
  array (
    'el_id' =>
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
      'width' => 110,
      'hidden' => true,
      'editable' => false,
      'in_list' => false,
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
    'level_id' =>
    array (
      'type' => 'table:member_lv@b2c',
      'required' => true,
      'default' => 0,
      'pkey' => true,
      'editable' => false,
      'comment' => app::get('shangou')->_('会员等级ID'),
    ),
   'marketable' =>
    array (
      'type' => 'bool',
      'default' => 'true',
      'label' =>  app::get('shangou')->_('该活动是否上架'),
      'width' => 110,
      'editable' => false,
    ),
  ),
  'comment' => app::get('shangou')->_('闪购活动与会员等级关联表'),
);
