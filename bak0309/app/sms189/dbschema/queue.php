<?php

$db['queue'] = array(
    'comment' => '短信发送队列',
    'columns' => array(
        'queue_id' => array(
            'type' => 'mediumint(8) unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'label' => 'ID',
            'comment' => ''
        ),
        'mobile' => array(
            'type' => 'char(11)',
            'required' => true,
            'label' => app::get('sms189')->_('手机号'),
            'comment' => ''
        ),
    	'template_id' => array(
    			'type' => 'varchar(25)',
    			'required' => true,
    			'label' => app::get('sms189')->_('电信短信模版ID'),
    	),
        'content' => array(
            'type' => 'text',
            'label' => app::get('sms189')->_('内容'),
            'comment' => ''
        ),
        'createtime' => array(
            'type' => 'time',
            'required' => false,
            'label' => app::get('sms189')->_('创建时间'),
            'comment' => ''
        )
    ),
    'engine' => 'innodb',
    'version' => '$Rev: $'

);
