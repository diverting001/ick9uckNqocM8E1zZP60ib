<?php

$db['openid'] = array(
    'columns' => array(
        'id' =>
        array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
            'comment' => app::get('weixin')->_('ID'),
        ),
        'wxopenid' =>
        array(
            'type' => 'varchar(100)',
            'default' => '',
            'label' => app::get('weixin')->_('openID'),
            'in_list' => true,
            'default_in_list' => true,
        ),
        'wxunionid' =>
        array(
            'type' => 'varchar(100)',
            'default' => '',
            'label' => app::get('weixin')->_('wxunionid'),
            'in_list' => true,
            'default_in_list' => true,
        ),
        'status' => array(
            'type' => 'TINYINT(4)',
            'default' => '0',
            'label' => app::get('weixin')->_('状态：0未关注 ， 1 关注'),
            'in_list' => true,
            'default_in_list' => true,
        ),
        'create_time' => array(
            'type' => 'time',
            'label' => app::get('b2c')->_('创建时间'),
            'filtertype' => 'time',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'update_time' => array(
            'type' => 'time',
            'label' => app::get('b2c')->_('修改时间'),
            'filtertype' => 'time',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
        ),
    ),
    'version' => '$Rev $',
    'comment' => app::get('weixin')->_('openID 订阅状态'),
    'index' => array(
        'ind_wx_openid' => array(
            'columns' => array(
                'wxopenid', 'wxunionid'
            ),
            'prefix' => 'UNIQUE'
        ),
    ),
);
