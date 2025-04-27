<?php

class sms189_openapi_mobile extends sms189_openapi_base  {
	
	public function __construct($app){
	
	}
	
	/**
	 *  发送验证码短信
	 * @return $msg
	 */
	public  function sendcode(){
		$params = $_POST;
    	//进行验证
    	self::validate($params);
    	//验证成功
    	
    	//验证必须的参数
    	if(empty($params['mobile']) || empty($params['code']) || empty($params['template_id'])){
    		$this->error('PARAMS_ERROR','参数错误，数据获取失败');
    	}
    	
    	$msg = '';
    	$res = sms189_mobile::send_now($params['mobile'],$params['template_id'],array('code'=>$params['code']), $msg);
    	
    	if ($res === false) {
    		$this->error('SYSTEM_ERROR',$msg ? $msg : '系统错误，发送失败');
    	}else{
    		$this->success($msg);
    	}
	}
	
	/**
	 *  发送电信模版短信
	 * @return $msg
	 */
	public  function send(){
		$params = $_POST;
		//进行验证
		self::validate($params);
		//验证成功
		 
		//验证必须的参数
		if(empty($params['mobile']) ||  empty($params['template_id'])){
			$this->error('PARAMS_ERROR','参数错误，数据获取失败');
		}
		$mobile       =  $params['mobile'];
		$template_id  =  $params['template_id'];
		
		$sendparams = $params;
		unset($sendparams['mobile'],$sendparams['template_id'],$sendparams['api_test'],$sendparams['sign'],$sendparams['timestamp']);
		$msg = '';
		$res = sms189_mobile::send_now($mobile,$template_id,$sendparams, $msg);
		 
		if ($res === false) {
			$this->error('SYSTEM_ERROR',$msg ? $msg : '系统错误，发送失败');
		}else{
			$this->success($msg);
		}
	}
	
}