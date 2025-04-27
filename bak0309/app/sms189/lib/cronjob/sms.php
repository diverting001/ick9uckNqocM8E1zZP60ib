<?php
class sms189_cronjob_sms{
    
    /**
    * 处理短消息队列
    *
    */
    function flush_queue(){
    	@set_time_limit(0);
    	@ini_set('memory_limit','512M');
//         $time = app::get('sms189')->getConf('sms_exec_time');
//         if (!empty($time) && time() - $time < 180) return;
//         app::get('sms189')->setConf('sms_exec_time', time());
        
        while ( sms189_mobile::flush_queue() );
    }

}