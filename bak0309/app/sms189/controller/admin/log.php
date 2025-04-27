<?php
class sms189_ctl_admin_log extends desktop_controller{
    
    public function index(){
    	
        $params = array(
            'title'=>'电信短信发送列表',
        	'actions' => array(
        		array(
        		 'label' => '群发短信-邀请码',
        				'href' => 'index.php?app=sms189&ctl=admin_log&act=qunfa',
        				'target' => "dialog::{width:450,height:500,title:'群发短信-邀请码'}",
        		),
        		array(
        			'label' => '群发短信-通用',
        			'href' => 'index.php?app=sms189&ctl=admin_log&act=qunfa2',
        			'target' => "dialog::{width:550,height:500,title:'群发短信-通用'}",
        		),
        	),
            'use_buildin_recycle'=>false,
            'use_buildin_selectrow'=>true,
            'use_buildin_filter'=>true,
            'orderBy' => 'sendtime DESC',
        );
        $this->finder('sms189_mdl_log',$params);
    }

    
    
    /**
     * 重试电信短信
     *
     * @param  int  $log_id 待重试的日志ID
     */
    public function retry($log_id = null) {
    	$this->pagedata['log_id'] = $log_id;
    	$this->display("admin/log/log_retry.html");
    }
    
    /**
     * 处理重试机制
     */
    function retry_do() {
    	set_time_limit(0);
    	$log_id = urldecode($_GET['log_id']);
    	$logItem = $this->app->model('log')->dump($log_id);
    	$return = sms189_mobile::auto_retry($logItem);
    	
    	$rs = $this->app->model('log')->dump($log_id,'status,msg');
    	
    	$rs['return']  =$return;

    	exit(json_encode($rs));
    }
    
    

    /**
     * 获取短信送达状态
     *
     * @param  int  $log_id 待重试的日志ID
     */
    public function feedback($log_id = null) {
    	$this->pagedata['log_id'] = $log_id;
    	$this->display("admin/log/log_feedback.html");
    }
    
    /**
     * 处理短信送达状态
     */
    function feedback_do() {
    	set_time_limit(0);
    	$log_id = urldecode($_GET['log_id']);
    	$logItem = $this->app->model('log')->dump($log_id);
    	$return = sms189_mobile::auto_feedback($logItem);
    	$rs = array(
    			'msg'=>$return === false ?'短信没有发送成功':'短信已经成功发送',
    	);
    	 
    	exit(json_encode($rs));
    }
    
    
    
    function qunfa(){
    	
    	$this->display("admin/sms/qunfasms.html");
    }
    
	function sms_queue(){
       $this->begin();
       $params['yaoqingma'] = trim($_POST['yaoqingma']);
       $params['youhuima'] = trim($_POST['youhuima']);
       $params['youhuijine'] = trim($_POST['youhuijine']);
       $template_id = trim($_POST['template_id']);
       
       $content = json_encode($params);
       
       $queue_data = array(
       		'content' => $content,
       		'template_id'=>$template_id,
       );
       $mobilearray = explode("\n",$_POST['mobiles']);
       $mobilearray = array_unique($mobilearray);
       $queue_mdl = app::get('sms189')->model('queue');
       if (!empty($mobilearray)) {
       	foreach ( $mobilearray as $mob){
       		$mob = trim($mob);
       		if (!empty($mob)) {
       			if (sms189_mobile::is_mobile($mob) === true){
       				//可以发送
       				$queue_data['mobile'] = $mob;
       				$queue_data['createtime'] = time();
       				logger::info(var_export($queue_data,1));
       				$queue_mdl->insert($queue_data);
       				unset($queue_data['queue_id']);
       			}
       		}
       	}
       }
       
       $this->end(true,app::get('b2c')->_('操作成功！'));
    }
    
    function qunfa2(){
    	 
    	$this->display("admin/sms/qunfasms2.html");
    }
    
    function sms_queue2(){
    	$this->begin();
    	//处理参数
    	$content = array();
    	 $params = $_POST['params'];
    	 foreach ($params as $key=>$val){
    	 	if (!empty($val)) {
    	 		$content['param'.$key] = $val;
    	 	}
    	 }
    	 
    	 if (!empty($content)) {
    	 	$content = json_encode($content);
    	 }else{
    	 	$content = '';
    	 }
    	
    	$template_id = trim($_POST['template_id']);
    	$queue_data = array(
    			'template_id'=>$template_id,
    			'content'    =>$content,
    	);
    	$mobilearray = explode("\n",$_POST['mobiles']);
    	$mobilearray = array_unique($mobilearray);
    	$queue_mdl = app::get('sms189')->model('queue');
    	if (!empty($mobilearray)) {
    		foreach ( $mobilearray as $mob){
    			$mob = trim($mob);
    			if (!empty($mob)) {
    				if (sms189_mobile::is_mobile($mob) === true){
    					//可以发送
    					$queue_data['mobile'] = $mob;
    					$queue_data['createtime'] = time();
    					logger::info(var_export($queue_data,1));
    					$queue_mdl->insert($queue_data);
    					unset($queue_data['queue_id']);
    				}
    			}
    		}
    	}
    	 
    	$this->end(true,app::get('b2c')->_('操作成功！'));
    }
    
    function email(){
        $params = array(
            'title'=>'邮箱验证码发送列表'
        );
        $this->finder('sms189_mdl_email_log',$params);
    }
}

