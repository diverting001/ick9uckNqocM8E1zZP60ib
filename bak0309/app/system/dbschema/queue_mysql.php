<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2013 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

$db['queue_mysql']=array (
    'columns' => array (
        'id' => array (
            'type' => 'bigint unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'comment' => app::get('system')->_('ID'),
        ),
        'queue_name' => array (
            'type' => 'varchar(100)',
            'comment' => app::get('system')->_('队列标识'),
            'required' => true,
        ),
        'worker'=>array(
            'type' => 'varchar(100)',
            'required' => true,
            'comment' => app::get('system')->_('执行任务类'),
        ),
        'params'=>array(
            'type' => 'longtext',
            'required' => true,
            'comment' => app::get('system')->_('任务参数'),
        ),
        'create_time' => array (
            'type' => 'time',
            'default' => 0,
            'comment' => '进入队列的时间',
        ),
        'last_cosume_time' => array(
            'type' => 'time',
            'default' => 0,
            'comment' => '任务开始执行时间'
        ),
        'owner_thread_id' => array (
            'type' => 'int',
            'default' => -1,
            'comment' => 'mysql进程ID',
        ),
    ),
    'index' => array (
        'ind_get' => 
        array (
            'columns' => 
            array (
                0 => 'queue_name',
                1 => 'owner_thread_id',
            ),
        ),
    ),
    'engine' => 'innodb',
    'version' => '$Rev: 40912 $',
    'ignore_cache' => true,
);