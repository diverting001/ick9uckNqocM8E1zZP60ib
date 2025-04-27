<?php

class sms189_mobile {
    const SEND_LIMIT = 60;
    const QUEUE_EMPTY = 'queue_empty';
    const ACCOUNT_ERROR = 'account_error';
    const SMS_ZERO = 'sms_zero';
    const TOKEN_API_URL = 'https://oauth.api.189.cn/emp/oauth2/v3/access_token';


    // 发送验证码专用
    public static function send($params, &$error_msg = "")
    {
   		 if (self::is_mobile($params['mobile']) === false) {
   		 	$record = array(
   		 			'data' => $params,
   		 			'res' => false,
   		 			'timestamp' => time()
   		 	);
            self::callback_send( $record, app::get('sms189')->_('手机号不合要求') );
            $error_msg = app::get('sms189')->_('手机号不合要求');
            return false;
         }


        if (!sms189_limit_filter::limit_and_update_for_phonenumber($params['mobile'])){
            logger::logtestkv("action.smsmgr.send", array("act"=>'limited', "mobile"=>$params['mobile']));
            $error_msg = app::get('sms189')->_('发送过于频繁，请1小时后再试');
            return false;
        }

         //发送短信
         //生成 or  获取手机验证码
         $msg = '';
         if(strlen($params['code']) <=0){
            $content['code'] =  app::get('sms189')->model('log')->gen_code($params['mobile'],isset($params['code_length']) ? $params['code_length'] : 6);
         }else{
            $content['code'] = $params['code'];
         }
         $params['code'] = $content['code'];
         $params['content'] = json_encode($content);
         $params['template_id'] = app::get('sms189')->getConf('ecos.leho.sms189.template_id');
         //TODO(yi.qian)添加日志信息
         logger::log("neigou=sms189_mobile::send code={$content['code']} content={$params['content']} template_id={$params['template_id']}",3);
         $result = sms189_rpc_bridge::request('send', $params, $msg);
         return $result;
    }

    /**
     * 发送中粮验证码
     * @param $params
     * @param string $error_msg
     * @return bool|mixed
     */
    public static function sendZhongLiang($params, &$error_msg = "")
    {
        if (self::is_mobile($params['mobile']) === false) {
            $record = array(
                'data' => $params,
                'res' => false,
                'timestamp' => time()
            );
            self::callback_send( $record, app::get('sms189')->_('手机号不合要求') );
            $error_msg = app::get('sms189')->_('手机号不合要求');
            return false;
        }

        if (!sms189_limit_filter::limit_and_update_for_phonenumber($params['mobile'])){
            logger::logtestkv("action.smsmgr.send", array("act"=>'limited', "mobile"=>$params['mobile']));
            $error_msg = app::get('sms189')->_('发送过于频繁，请1小时后再试');
            return false;
        }

        if(strlen($params['code']) <=0){
            $params['code'] =  app::get('sms189')->model('log')->gen_code($params['mobile'], isset($params['code_length']) ? $params['code_length'] : 6);
        }

        $remotequeue_service = kernel::service("remotequeue.service");
        $sms_content = "您的中粮我买网广铁集团专享购激活验证码是：{$params['code']}，请在15分钟内完成验证，如非本人操作，请忽略本条短信。回复TD退订";
        $sms_mobile = $params['mobile'];
        $type = "diandiSendSMS";
        $script = 'common.service.proxy.sendSMS';

        $post_data = array(
            'type' => $type,
            'mobile' => $sms_mobile,
            'content' => $sms_content,
        );
        $sms_post_data[] = $post_data;
        $result = $remotequeue_service->dispatchScriptCommandTaskSimple($script,json_encode($sms_post_data));
        $success = $result === false ? false : true;
        if(!$success){
            $error_msg = '发送失败';
        }

        //记录返回信息
        $record = array(
            'data' => array(
                array(
                    'mobile' => $params['mobile'],
                    'content' => json_encode(array('code' => $params['code'])),
                    'template_id' => app::get('sms189')->getConf('ecos.leho.sms189.template_id'),
                    'mobile_code' => $params['code']
                )
            ),
            'res' => $success,
            'timestamp' => time(),
            'info' => $success ? '1' : $error_msg
        );

        self::callback_send($record, $record['info']);

        return $success;
    }


    // 发送实际短信内容专用
    public static function sendContent($mobile, $sms_content,$message_channel = "")
    {
        logger::log("neigou=sms189_mobile::sendContent mobile=".$mobile);
        return sms189_rpc_request_smsutil::sendSMS($mobile, $sms_content,$message_channel);
    }

    // 发送实际短信内容专用【点滴关怀】
    public static function sendContentByDianDi($mobile, $sms_content)
    {
        logger::log("neigou=sms189_mobile::sendContentByDianDi mobile=".$mobile);
        return sms189_rpc_request_smsutil::diandiB2cSendSMS($mobile, $sms_content,'op');
    }

    //生成手机验证码
    public function generate_code($mobile)
    {
        if(empty($mobile)){
            return false;
        }
        $code = rand(1,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9);
        return $code;
    }

    public static function callback_send($result, $msg)
    {
        app::get('sms189')->model('log')->batch_insert($result['data'], $msg, $result['res'] === true ? 'succ' : 'fail');
    }


    public static function callback_servertime($result, $msg)
    {
        app::get('sms189')->setConf('servertime', $result);
    }

    public static function is_mobile($mobile)
    {
        return (is_numeric($mobile) && strlen($mobile) == 11) ? true : false;
    }

    /**
     * 获取/刷新token信息
     * @param array $postdata
     * @param string $url  API_URI
     * @param array $options  curl 选择属性
     * @return array
     */
    public static  function curl_post($postdata=array(),$url=self::TOKEN_API_URL, $options=array()){
    	$poststing = '';
    	ksort($postdata);
    	foreach ($postdata as $k=>$v){
    		$poststing .= $k.'='.$v.'&';
    	}
    	$poststing = substr($poststing,0,-1);

    	$ch = curl_init();
        logger::log("neigou=curl_post url={$url}", 3);
    	curl_setopt($ch, CURLOPT_URL,$url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $poststing);
    	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
    	if (!empty($options)){
    		curl_setopt_array($ch, $options);
    	}
        logger::log("neigou=curl_post step=curl_exec", 3);
        $data = "";
        try{
            $data = curl_exec($ch);
        }
        catch(Exception $e){
            logger::log("neigou=curl_post exception={$e->getMessage()}", 3);
        }
        logger::log("neigou=curl_post url={$url} data=$data", 3);
        curl_close($ch);
    	return $data;
    }

    public static function flush_queue()
    {
    	$return = self::queuesend(array(), $msg);
    	if ( $return === self::QUEUE_EMPTY || $return === false) {
    		return false;
    	} else {

    		return true;
    	}
    }


    public static function queuesend($filter=array(), &$msg)
    {
    	if (!is_array($filter))

    		return false;

    	$queueModel = app::get('sms189')->model('queue');
    	$data = $queueModel->getList('*', $filter, 0, self::SEND_LIMIT);
    	if (empty($data)) return self::QUEUE_EMPTY;

    	$queueIds = array();
    	foreach ( $data as $val ) $queueIds[] = $val['queue_id'];
    	$queueModel->delete(array('queue_id' => $queueIds));
    	foreach ($data as $key => $val) {
    		if (self::is_mobile($val['mobile']) === false) {
    			$error_contents[] = $val;
    			continue;
    		}

    		//发送短信
    		sms189_rpc_bridge::request('queuesend', $val, $msg);
    	}
    	if (is_array($error_contents)) {
    		$record = array(
    				'data' => $error_contents,
    				'res' => false,
    				'timestamp' => time()
    		);
    		self::callback_queuesend( $record, app::get('sms189')->_('手机号不合要求') );
    	}

    	return true;
    }


    public static function callback_queuesend($result, $msg)
    {
    	app::get('sms189')->model('log')->batch_insert($result['data'], $msg, $result['res'] === true ? 'succ' : 'fail');
    }


    /**
     *  通用短信接口,立即发送短信
     * @param int $mobile  手机号
     * @param string|int $template_id  模版ID
     * @param array|null $params  参数，可为空
     * @return boolean
     */
    public static function send_now($mobile,$template_id=null,$params =array(),&$msg)
    {
    	if (self::is_mobile($mobile) === false) {
    		$record = array(
    				'data' => $params,
    				'res' => false,
    				'timestamp' => time()
    		);
    		$msg = app::get('sms189')->_('手机号不合要求') ;
    		self::callback_send( $record, $msg);
    		return  false;
    	}
    	if (is_null($template_id) ||empty($template_id) ) {
    		$record = array(
    				'data' => $params,
    				'res' => false,
    				'timestamp' => time()
    		);
    		$msg =  app::get('sms189')->_('没有添加模版ID');
    		self::callback_send( $record, $msg );
    		return  false;
    	}
    	//发送短信
    	if (empty($params)) {
    		$content = "{}";
    	}else{
    		$content = json_encode($params);
    	}
    	$params['mobile'] = $mobile;
    	$params['content'] = $content;
    	$params['mobile']=$mobile;
    	$params['template_id'] =$template_id;
    	sms189_rpc_bridge::request('send', $params, $msg);
    	return true;
    }

    /**
     *
     * 聚优福利短信接口
     *
     * @param $params
     * @param string $error_msg
     *
     * @return bool
     */
    public static function sendJYFL($params, &$error_msg = "")
    {
        if (self::is_mobile($params['mobile']) === false) {
            $record = array(
                'data'      => $params,
                'res'       => false,
                'timestamp' => time(),
            );
            self::callback_send($record, app::get('sms189')->_('手机号不合要求'));
            $error_msg = app::get('sms189')->_('手机号不合要求');

            return false;
        }

        if (strlen($params['code']) <= 0) {
            $params['code'] = app::get('sms189')->model('log')->gen_code($params['mobile'],
                isset($params['code_length']) ? $params['code_length'] : 6);
        }

        $post_data = array(
            'phone'        => $params['mobile'],
            'code'         => $params['code'],
            'pid'          => '56V3b0c9',
            'templateCode' => '172883775',
            'bizType'      => '5',
        );

        $result = self::curl_post($post_data, JYFL_SMS_API);

        \Neigou\Logger::debug("passport.sms.sendJYFL", array('action' => 'sendJYFL','post_data' => $post_data,'result' => $result));

        $result = json_decode($result, true);

        if (isset($result['code']) && $result['code'] >= 0) {
            $sendRes = true;
        } else {
            $sendRes   = false;
            $error_msg = '发送失败.'.$result['message'];
        }

        //记录返回信息
        $record = array(
            'data'      => array(
                array(
                    'mobile'      => $params['mobile'],
                    'content'     => json_encode(array('code' => $params['code'])),
                    'template_id' => $post_data['templateCode'],
                    'mobile_code' => $params['code'],
                ),
            ),
            'res'       => $sendRes,
            'timestamp' => time(),
        );

        self::callback_send($record, $sendRes ? $result['data']['bizId'] : $result['code'].$result['message']);

        return $sendRes;
    }
}
