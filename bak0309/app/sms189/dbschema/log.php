<?php

$db['log'] = array(
    'comment' => '短信发送日志',
    'columns' => array(
        'log_id' => array(
            'type' => 'mediumint(8) unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'label' => 'ID',
        ),
        'mobile' => array(
            'type' => 'char(11)',
            'required' => true,
            'searchtype' => 'has',
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'label' => app::get('sms189')->_('手机号'),
        ),
    	'template_id' => array(
    		'type' => 'varchar(25)',
    		'required' => true,
    		'label' => app::get('sms189')->_('电信短信模版ID'),
    		'in_list' => true,
    	),
        'content' => array(
            'type' => 'text',
            'label' => app::get('sms189')->_('内容'),
        ),
    	'mobile_code' => array(
    		'type' => 'varchar(25)',
    		'required' => true,
    		'default' => '',
    		'in_list' => true,
    		'label' => app::get('sms189')->_('手机验证码'),
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
        'msgid' => array(
            'type' => 'varchar(32)',
            'required' => false,
            'label' => app::get('sms189')->_('标识符'),
        	'in_list' => true,
        	'default_in_list' => true,
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
        'ind_mobile' =>
            array (
                'columns' =>
                    array (
                        0 => 'mobile',
                    ),
            ),
	),
    'engine' => 'innodb',
    'version' => '$Rev: $'
);
