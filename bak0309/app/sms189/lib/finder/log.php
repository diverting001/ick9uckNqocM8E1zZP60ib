<?php
class sms189_finder_log{

    /* 详情
     *
     */
    function detail_log($log_id){
        
        $render = app::get('sms189')->render();
        $smslog = &app::get('sms189')->model("log");
        $apilog = $smslog->dump($log_id);
		$request = json_decode($apilog['content'],1);
		$apilog['content'] = var_export($request,1);
        $render->pagedata['apilog'] = $apilog;
        return $render->fetch("admin/log/log_detail.html");
       
    }
/*
    var $column_retry='重试操作';
    var $column_retry_width = 70;
    var $column_retry_order = 1;
    function column_retry($row){
        $log_id = $row['log_id'];
        $finder_id = $_GET['_finder']['finder_id'];
        $button = "<a href=\"index.php?app=sms189&ctl=admin_log&act=retry&p[0]={$log_id}&finder_id={$finder_id}\" target=\"dialog::{title:'短信发送重试', width:550, height:300}\">重试</a>";
        if ( $row['status'] == 'fail'){
            return $button;
        }
    }
    
    var $column_feedback='送达状态';
    var $column_feedback_width = 70;
    var $column_feedback_order = 2;
    function column_feedback($row){
    	$log_id = $row['log_id'];
    	$finder_id = $_GET['_finder']['finder_id'];
    	$button = "<a href=\"index.php?app=sms189&ctl=admin_log&act=feedback&p[0]={$log_id}&finder_id={$finder_id}\" target=\"dialog::{title:'短信送达状态获取', width:550, height:300}\">获取</a>";
    	if ( $row['status'] == 'succ' &&  $row['re_status'] == 'fail'){
    		return $button;
    	}
    }
*/
}
?>