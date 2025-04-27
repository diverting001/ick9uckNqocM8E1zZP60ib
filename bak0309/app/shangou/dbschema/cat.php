<?php
$db['cat']=array (
  'columns' =>
  array (
    'cat_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => app::get('shangou')->_('频道ID'),
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'parent_id' =>
    array (
      'type' => 'number',
      'label' => app::get('shangou')->_('频道ID'),
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'parent_id'=>true,
    ),
    'cat_path' =>
    array (
      'type' => 'varchar(100)',
      'default' => ',',
      'label' => app::get('shangou')->_('频道路径(从根至本结点的路径,逗号分隔,首部有逗号)'),
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    'is_leaf' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'label' => app::get('shangou')->_('是否叶子结点（true：是；false：否）'),
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    'cat_name' =>
    array (
      'type' => 'varchar(100)',
      'required' => true,
      'is_title' => true,
      'default' => '',
      'label' => app::get('shangou')->_('频道名称'),
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'gallery_setting' =>
    array(
        'type' => 'serialize',
        'label' => app::get('shangou')->_('活动频道设置'),
        'deny_export' => true,
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'required' => true,
      'label' => app::get('shangou')->_('是否屏蔽（true：是；false：否）'),
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    'p_order' =>
    array (
      'type' => 'number',
      'label' => app::get('shangou')->_('排序'),
      'width' => 110,
      'editable' => false,
      'default' => 0,
      'in_list' => true,
    ),
    'child_count' =>
    array (
      'type' => 'number',
      'default' => 0,
      'required' => true,
      'editable' => false,
      'comment' => app::get('shangou')->_('子类别数量'),
    ),
    'last_modify' =>
    array (
      'type' => 'last_modify',
      'label' => app::get('shangou')->_('更新时间'),
      'width' => 110,
      'in_list' => true,
      'orderby' => true,
    ),
  ),
  'index' =>
  array (
    'ind_cat_path' =>
    array (
      'columns' =>
      array (
        0 => 'cat_path',
      ),
    ),
    'ind_disabled' =>
    array (
      'columns' =>
      array (
        0 => 'disabled',
      ),
    ),
    'ind_last_modify' =>
    array (
      'columns' =>
      array (
        0 => 'last_modify',
      ),
    ),
  ),
  'version' => '$Rev: 41329 $',
  'comment' => app::get('shangou')->_('闪购活动频道'),
);
