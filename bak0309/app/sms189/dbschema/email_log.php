<?php

$db['email_log'] = array(
    'comment' => '邮箱发送日志',
    'columns' => array(
        'log_id' => array(
            'type' => 'mediumint(8) unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'label' => 'ID',
        ),
        'email' => array(
            'type' => 'varchar(30)',
            'required' => true,
            'searchtype' => 'has',
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'label' => app::get('sms189')->_('邮箱'),
        ),
        'content' => array(
            'type' => 'text',
            'label' => app::get('sms189')->_('内容'),
        ),
        'email_code' => array(
            'type' => 'varchar(25)',
            'required' => true,
            'default' => '',
            'in_list' => true,
            'label' => app::get('sms189')->_('邮箱验证码'),
        ),
        'status' => array(
            'type' => array(
                'succ' => '成功', 'fail' => '失败'
            ),
            'required' => true,
            'default' => 'fail',
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'label' => app::get('sms189')->_('发送状态'),
        ),
        'sendtime' => array(
            'type' => 'time',
            'required' => false,
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => 'time',
            'filterdefault' => true,
            'label' => app::get('sms189')->_('发送时间'),
        ),
        'msg' => array(
            'type' => 'text',
            'required' => false,
            'label' => app::get('sms189')->_('错误信息'),
            'in_list' => true,
        ),
    ),
    'index' =>array (
        'ind_status' =>
            array (
                'columns' =>
                    array (
                        0 => 'status',
                    ),
            ),
        'ind_email' =>
            array (
                'columns' =>
                    array (
                        0 => 'email',
                    ),
            ),
    ),
    'engine' => 'innodb',
    'version' => '$Rev: $'
);
