<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shop
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

define('QUEUE_SCHEDULE', 'system_queue_adapter_mysql');
define('DEFAULT_PUBLISH_QUEUE', 'normal');
#define('QUEUE_CONSUMER', 'fork');

$bindings = array(
    'crontab:b2c_tasks_cleancartobject' => array('slow'), 
    'crontab:site_tasks_createsitemaps' => array('slow'), 
    'crontab:ectools_tasks_statistic_day' => array('slow'),
    'crontab:ectools_tasks_statistic_hour' => array('slow'),
    'crontab:base_tasks_cleankvstore' => array('slow'),
    'crontab:operatorlog_tasks_cleanlogs' => array('slow'),
    
    'b2c_tasks_matrix_sendorders' => array('quick'),    
    'b2c_tasks_matrix_sendpayments' => array('quick'),
    'b2c_tasks_sendemail' => array('quick'),
    'b2c_tasks_sendsms' => array('quick'),
    'b2c_tasks_sendmsg' => array('quick'),
    'desktop_tasks_runimport' => array('normal'),
    'desktop_tasks_turntosdf' => array('normal'),
    'emailbus_tasks_sendemail' => array('slow'),
    'image_tasks_imagerebuild' => array('normal'),
    'recommended_tasks_update' => array('slow'),
    'other' => array('other'),
);

$queues = array(
    'slow' => array(
        'title' => 'slow queue',
        'thread' => 1),
    'quick' => array(
        'title' => 'quick queue',
        'thread' => 2),
    'normal' => array(
        'title' => 'normal queue',
        'thread' => 1));
