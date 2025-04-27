<?php

class sms189_rpc_request_mobile extends sms189_rpc_request
{
    const API_URL = 'http://61.135.198.131:8023/MWGate/wmgw.asmx/MongateSendSubmit'; //梦网短信模版
    //const API_URL = 'http://api.189.cn/v2/emp/templateSms/sendSms'; //模版短信
    const API_FEEDBACK_URL = 'http://api.189.cn/v2/EMP/nsagSms/appnotify/querysmsstatus';//模板短信状态报告查询
    public static $ver = '1.0';
    public static $back_format = 'json';
    public static $method = 'post';
    public $result = array();

        /** 短信发送接口
         *
         * @param array $params 参数数组
         * 		array(mobile=>'xxx',app_tmpl_id=>xxx,args=>'xxxx')
         * 		@param int $app_tmpl_id 电信短信模版ID
         * 		@param string mobile 手机号
         * 		@param string  args 序列化的参数
         * @return mix
         */
    public function active_send($params=array(), $type='set')
    {
        $result = $this->active('send', $params);
        //记录返回信息
        $record = array(
                'data' => array($params),
                'res' => $result === false ? false : true,
                'timestamp' => time(),
            );
        $record['info'] =  $result === false ? $this->result['res_message'] : $this->result['idertifier'];

        sms189_rpc_bridge::response('send', $record, $record['info']);
        //logger::log("neigou=sms189_rpc_request_mobile::active_send record=".print_r($record,true),3);

        return $result;
    }

    protected function active($which, $params=array(), $method='')
    {
        $token = self::_get_token(); //token

        if ($token === false || empty($token)) {
        	$result = array(
        		'res_code' => 99999999,
        		'res_message'=>'Token 获取失败'
        	);
        	return false;
        }
        $remotequeue_service = kernel::service("remotequeue.service");
        $sms_content = "您的验证码为：{$params['code']}，请在15分钟内完成验证，如非本人操作，请忽略本条短信。";
        $sms_mobile = $params['mobile'];
        $type = "diandiSendSMS";
        $script = 'common.service.proxy.sendSMS';

        $post_data = array(
            'type' => $type,
            'mobile' => $sms_mobile,
            'content' => $sms_content,
        );

        if($params['message_channel']){
            $post_data['message_channel'] = $params['message_channel'];
        }
        
        $sms_post_data[] = $post_data;
        $remotequeue_service->dispatchScriptCommandTaskSimple($script,json_encode($sms_post_data));

        $msg = 'Success';
        $status = 0;
        $result = array(
            'res_code'=>$status,
            'res_message'=>$msg,
            'idertifier'=>'1',
        );

        //$result = sms189_rpc_request_smsutil::sendSMS($params['mobile'], "验证码：{$params['code']}，请在15分钟内完成验证，如非本人操作，请忽略本短信。");
        $this->result = array_merge($this->result, $result);
        logger::log("neigou=sms189_rpc_request_mobile::active result=".print_r($result,true),7);
        if($result['res_code'] == 0)
            return $result['idertifier'];
        else
            return false;
    }

    private  static function _get_token(){
    	$tokeninfo = app::get('sms189')->getConf('ecos.leho.sms189.tokeninfo');

    	if (empty($tokeninfo)) {
    		$tokeninfo = array();
    	}
    	
    	$tokeninfo['access_token'] = '40597f3c56688f016fe7fcd97adf53a11431174133919';
        //TODO(yi.qian)获取token的接口已经无效，待调查。
        /*
    	logger::log("#######json_decode-tokeninfo -- start".$tokeninfo['access_token'], LOG_SYS_DEBUG);
    	if (empty($tokeninfo)|| empty($tokeninfo['access_token']) || $tokeninfo['expires_in'] <= time() + 10) {
    		//重新获取token
    		$params['grant_type'] = 'client_credentials'; //授权模式 目前是CC授权
    		$params['app_id']     = app::get('sms189')->getConf('ecos.leho.sms189.appid'); //APP ID
    		$params['app_secret'] = app::get('sms189')->getConf('ecos.leho.sms189.appsecret'); //APP 密钥

            logger::log("neigou=sms189_rpc_request_mobile::active step=request_token",3);
    		$tokeninfo = json_decode(sms189_mobile::curl_post($params), 1);
            logger::log("neigou=sms189_rpc_request_mobile::active token_info={$tokeninfo}",3);

    		
    		if ($tokeninfo['res_code']==='0') {
    			$tokeninfo['expires_in'] = time() + $tokeninfo['expires_in'];
    			$tokeninfo['app_id'] = $params['app_id'];
    			$tokeninfo['app_secret'] = $params['app_secret'];
    			app::get('sms189')->setConf('ecos.leho.sms189.tokeninfo',$tokeninfo);
    		}else{
    			return false;
    		}
    	}
        */
    	return $tokeninfo['access_token'];
    }

    private static function _get_sign($params)
    {
        ksort($params);
        $psting = '';
        foreach ($params as $k=>$v){
        	$psting .= $k.'='.$v.'&';
        }
        $psting = substr($psting,0,-1);
        $sign = self::get_signature($psting,app::get('sms189')->getConf('ecos.leho.sms189.appsecret'));
        return $sign;
    }

    /**
     * 使用HMAC-SHA1算法生成oauth_signature签名值
     * @param $str 需要加密的字符串
     * @param $appSecret  密钥
     * @return 签名值
     */

    private static  function get_signature($str, $appSecret)
    {
    	$signature = "";
    	if (function_exists('hash_hmac')){
    		$signature = base64_encode(hash_hmac("sha1", $str, $appSecret, true));
    	}else{
    		$blocksize    = 64;
    		$hashfunc    = 'sha1';
    		if (strlen($appSecret) > $blocksize){
    			$appSecret = pack('H*', $hashfunc($appSecret));
    		}
    		$appSecret    = str_pad($appSecret,$blocksize,chr(0x00));
    		$ipad    = str_repeat(chr(0x36),$blocksize);
    		$opad    = str_repeat(chr(0x5c),$blocksize);
    		$hmac     = pack(
    				'H*',$hashfunc(
    						($appSecret^$opad).pack(
    								'H*',$hashfunc(
    										($appSecret^$ipad).$str
    								)
    						)
    				)
    		);
    		$signature = base64_encode($hmac);
    	}

    	return $signature;
    }


    /** 队列短信发送接口
     *
     * @param array $params 参数数组
     * 		array(mobile=>'xxx',app_tmpl_id=>xxx,args=>'xxxx')
     * 		@param int $app_tmpl_id 电信短信模版ID
     * 		@param string mobile 手机号
     * 		@param string  args 序列化的参数
     * @return mix
     */
    public function active_queuesend($params=array(), $type='set')
    {

    	$result = $this->active('queuesend', $params);
    	//记录返回信息
    	$record = array(
    			'data' => array($params),
    			'res' => $result === false ? false : true,
    			'timestamp' => time(),
    	);
    	$record['info'] =  $result === false ? $this->result['res_message']: $this->result['idertifier'];

    	sms189_rpc_bridge::response('queuesend', $record, $record['info']);

    	return $result;
    }
}
