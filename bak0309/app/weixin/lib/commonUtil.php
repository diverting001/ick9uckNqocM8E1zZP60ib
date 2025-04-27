<?php

class weixin_commonUtil{
	
	protected $unifiedorderurl = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    /**
     *
     *
     * @param toURL
     * @param paras
     * @return
     */
    static function genAllUrl($toURL, $paras){
        $allUrl = null;
        if(null == $toURL){
            die("toURL is null");
        }
        if (strripos($toURL,"?") =="") {
            $allUrl = $toURL . "?" . $paras;
        }else {
            $allUrl = $toURL . "&" . $paras;
        }

        return $allUrl;
    }

    static function create_noncestr( $length = 16 ){
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }

        return $str;
    }

    /**
     *
     *
     * @param src
     * @param token
     * @return
     */
    static function splitParaStr($src, $token){
        $resMap = array();
        $items = explode($token,$src);
        foreach ($items as $item){
            $paraAndValue = explode("=",$item);
            if ($paraAndValue != "") {
                $resMap[$paraAndValue[0]] = $parameterValue[1];
            }
        }

        return $resMap;
    }

    /**
     * trim
     * @param  string $value param
     * @return string        trim param
     */
    static function trimString($value){
        $ret = null;
        if (null != $value) {
            $ret = $value;
            if (strlen($ret) == 0) {
                $ret = null;
            }
        }
        return $ret;
    }

    /**
     * formatQueryParaMap
     * @param  array $paraMap   params
     * @param  bool $urlencode ifurlencode
     * @return string            url
     */
    static function formatQueryParaMap($paraMap, $urlencode){
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v){
            if (null != $v && "null" != $v && "sign" != $k) {
                if($urlencode){
                   $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar;
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }

    static function formatBizQueryParaMap($paraMap, $urlencode){
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v){
            if($urlencode){
               $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar;
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }

        return $reqPar;
    }

    static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
             if (is_numeric($val))
             {
                 $xml.="<".$key.">".$val."</".$key.">";

             }
             else
                 $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
        $xml.="</xml>";

        return $xml;
    }

    static function sign($content, $key) {
        try {
            if (null == $key) {
               throw new Exception("财付通签名key不能为空！" . "<br>");
            }
            if (null == $content) {
               throw new Exception("财付通签名内容不能为空" . "<br>");
            }
            $signStr = $content . "&key=" . $key;

            return strtoupper(md5($signStr));
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    static function verifySignature($content, $sign, $md5Key) {
        $signStr = $content . "&key=" . $md5Key;
        $calculateSign = strtoupper(md5($signStr));
        //$tenpaySign = strtolower($sign);
        return $calculateSign == $sign;
    }

    static function sign_sha1($data,$paySignKey){
        foreach ($data as $k => $v){
            $signData[strtolower($k)] = $v;
        }

        try {
            if($paySignKey == ""){
                throw new Exception("APPKEY为空！" . "<br>");
            }
            $signData["appkey"] = $paySignKey;
            ksort($signData);
            $signData = self::formatBizQueryParaMap($signData, false);
            return sha1($signData);
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    static function verifySignatureShal($postData, $sign) {
        $payData = app::get('ectools')->getConf('weixin_payment_plugin_wxpay');
        $payData = unserialize($payData);
        $postData['appid'] = trim($payData['setting']['appId']);
        $paySignKey = trim($payData['setting']['paySignKey']); // 财付通商户权限密钥 Key

        $calculateSign = strtolower(self::sign_sha1($postData,self::trimString($paySignKey)));
        $tenpaySign = strtolower($sign);
        return $calculateSign == $tenpaySign;
    }
    /**
     * 	作用：将xml转为array
     */
    static  public function xmlToArray($xml)
    {
    	//将XML转为array
    	$array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    	return $array_data;
    }
    
    static function getPrepayId($appid,$paykey,$params){
    	
	    $dataparams = $params;
	    $dataparams['appid'] = $appid;
	    $dataparams['nonce_str'] = self::create_noncestr();//随机字符串
	   
	    ksort($dataparams);
	    $paramstr = self::formatBizQueryParaMap($dataparams,false);
	    $dataparams["sign"] = self::sign($paramstr,$paykey);//签名
    	$xml  =   self::arrayToXml($dataparams);
        try{
            $response = self::postXmlCurl($xml, 'https://api.mch.weixin.qq.com/pay/unifiedorder',10);
        }catch (Exception $e) {
            \Neigou\Logger::General('wxpay.prepay_id_exception','getPrepayId response Exception =>'.$e->getMessage());
        }
        \Neigou\Logger::General('wxpay.prepay_id_exception_response',array('param'=>$dataparams,'response'=>$response));
        $prepay_id = '';
        if($response !== false){
	        $result = self::xmlToArray($response);
	    	$prepay_id = $result["prepay_id"];
        }
    	return $prepay_id;
    }

    static function getPrepayInfo($appid,$paykey,$params){

        $dataparams = $params;
        $dataparams['appid'] = $appid;
        $dataparams['nonce_str'] = self::create_noncestr();//随机字符串

        ksort($dataparams);
        $paramstr = self::formatBizQueryParaMap($dataparams,false);
        $dataparams["sign"] = self::sign($paramstr,$paykey);//签名
        $xml  =   self::arrayToXml($dataparams);
        logger::log('get_prepay_id $xml =>'.var_export($xml,1));
        try{
            $response = self::postXmlCurl($xml, 'https://api.mch.weixin.qq.com/pay/unifiedorder',10);
        }catch (Exception $e) {
            logger::log('getPrepayId response Exception =>'.$e->getMessage());
        }
        logger::log('get_prepay_id $response =>'.var_export($response,1));
        $result = array();
        if($response !== false){
            $result = self::xmlToArray($response);
        }
        return $result;
    }
    
    static function getRefundStatus($appid,$paykey,$params){

        $dataparams = $params;
        $dataparams['appid'] = $appid;
        $dataparams['nonce_str'] = self::create_noncestr();//随机字符串

        ksort($dataparams);
        $paramstr = self::formatBizQueryParaMap($dataparams,false);
        $dataparams["sign"] = self::sign($paramstr,$paykey);//签名
        $xml  =   self::arrayToXml($dataparams);
        logger::log('get_prepay_id $xml =>'.var_export($xml,1));
        try{
            $response = self::postXmlCurl($xml, 'https://api.mch.weixin.qq.com/pay/refundquery',10);
        }catch (Exception $e) {
            logger::log('getPrepayId response Exception =>'.$e->getMessage());
        }
        logger::log('get_prepay_id $response =>'.var_export($response,1));
        $result = array();
        if($response !== false){
            $result = self::xmlToArray($response);
        }
        return $result;
    }
    
    static function getRefundInfo($appid, $paykey, $params , $weixinCertPath) {

        $dataparams = $params;
        $dataparams['appid'] = $appid;
        $dataparams['nonce_str'] = self::create_noncestr(); //随机字符串

        ksort($dataparams);
        $paramstr = self::formatBizQueryParaMap($dataparams, false);
        $dataparams["sign"] = self::sign($paramstr, $paykey); //签名
        $xml = self::arrayToXml($dataparams);
        logger::log('get_prepay_id $xml =>' . var_export($xml, 1));
        try {
            $response = self::postXmlCurlSSL($xml, 'https://api.mch.weixin.qq.com/secapi/pay/refund', $weixinCertPath, 10);
        } catch (Exception $e) {
            logger::log('getRefundInfo response Exception =>' . $e->getMessage());
        }
        logger::log('getRefundInfo $response =>' . var_export($response, 1));
        $result = array();
        if ($response !== false) {
            $result = self::xmlToArray($response);
        }
        return $result;
    }

    /**
     * 	作用：以post方式提交xml到对应的接口url
     */
    static  function postXmlCurl($xml,$url,$second=30)
    {
    	
	    //初始化curl
	    	$ch = curl_init();
	    	//设置超时
	    	curl_setopt($ch, CURLOPT_TIMEOUT, $second);
	    	//这里设置代理，如果有的话
	    	
	    	curl_setopt($ch,CURLOPT_URL, $url);
	    	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
	    	curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
	    	//设置header
	    	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	    	//要求结果为字符串且输出到屏幕上
	    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	    	//post提交方式
	    	curl_setopt($ch, CURLOPT_POST, TRUE);
	    	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	    	//运行curl
	    	$data = curl_exec($ch);
	    	curl_close($ch);
	    	//返回结果
	    	if($data)
	    	{
	    		return $data;
	    	}
	    	else
	    	{
	    		return false;
	    	}
    }
    
    /**
     * 	作用：以post方式提交xml到对应的接口url
     */
    static function postXmlCurlSSL($xml, $url, $weixinCertPath, $second = 30, $aHeader = array()) {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        //证书
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $weixinCertPath . "/apiclient_cert.pem");
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $weixinCertPath . "/apiclient_key.pem");

        //设置header
        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }

        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            \Neigou\Logger::General("postXmlCurlSSL", array(
                'xml' => $xml,
                'url' => $url,
                'weixinCertPath' => $weixinCertPath,
                'data' => $data,
                'apiclient_cert' => file_get_contents($weixinCertPath . "/apiclient_cert.pem"),
                'apiclient_key' => file_get_contents($weixinCertPath . "/apiclient_key.pem"),
                'error' => $error
            ));
            curl_close($ch);
            return false;
        }
    }

    static function getJsapiTicketByAccessToken($access_token = ''){
        if(!$access_token) return '';
        $_redis = kernel::single('base_sharedkvstore');
        $p = $_redis -> fetch('jsapi_ticket',$access_token,$ticket);
        if(!$p || !$ticket){
            $url = sprintf("https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=%s",$access_token);
            $curl = new \Neigou\Curl();
            if (NEIGOU_HTTP_PROXY){
                $optConfig = array(
                    CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
                    CURLOPT_PROXY => NEIGOU_HTTP_PROXY,
                );
                $curl->SetOpt($optConfig);
                $curl->SetHeader('Content-Type', 'application/x-www-form-urlencoded');
            }
            $result = $curl -> Get($url);
            \Neigou\Logger::General("wx.getJsapiTicketByAccessToken", array('data' => $result));
            $result = json_decode($result,true);
            if($result && $result['ticket']){
                $ticket = $result['ticket'];
                $_redis -> store('jsapi_ticket',$access_token,$ticket,7100);
            }else{
                $ticket = '';
            }
        }
        return $ticket;
    }

    static function getJsapiConfig($access_token = '',$current_url = ''){
        if(!$current_url || !$access_token){
            return array();
        }

        $ticket = self::getJsapiTicketByAccessToken($access_token);

        $data = array();
        $data['noncestr'] = self::create_noncestr();
        $data['jsapi_ticket'] = $ticket;
        $data['timestamp'] = time();
        $data['url'] = $current_url;
        \Neigou\Logger::General("weixin_commonUtil.getJsapiConfig", array('data' => $data, "sparam1"=>$access_token));

        ksort($data);

        $str = array();
        foreach ($data as $k => $v) {
            $str[] = "{$k}={$v}";
        }

        $str = implode('&',$str);
        $str = sha1($str);
        $data['signature'] = $str;
        return $data;

    }

    static function getJsapiConfigFromAgh($url = '',$qyCorpId = '', $suiteId=''){
        $api = 'https://apps.51guanhuai.com/third/api/weixin/jsonpQywxSharePackage?jsonp=0&url='.$url.'&qyCorpId='.$qyCorpId.'&suiteId='.$suiteId;
        $curl = new \Neigou\Curl();
        $response = $curl->Get($api);
        $res = json_decode($response,true);
        if($res && $res['errno']==0){
            return $res['body'];
        } else {
            \Neigou\Logger::General('store.wxqypay.err',array(
                'url'=>$api,
                'response'=>$response,
                'res'=>$res,
            ));
            die('用户状态异常，请联系客服处理[jsapi_config]');
        }
    }

    static function getQyWeixinOpenId($access_token,$code){
        if(!$access_token || !$code){
            \Neigou\Logger::General("wx.getQyWeixinOpenId.0", array('params' => ''));
            return '';
        }

        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token={$access_token}&code={$code}";
        $curl = new \Neigou\Curl();
        $curl -> time_out = 10;
        if (NEIGOU_HTTP_PROXY){
            $optConfig = array(
                CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
                CURLOPT_PROXY => NEIGOU_HTTP_PROXY,
            );
            $curl->SetOpt($optConfig);
            $curl->SetHeader('Content-Type', 'application/x-www-form-urlencoded');
        }
        $result = $curl -> Get($url);
        $result = json_decode($result,true);
        \Neigou\Logger::General("wx.getQyWeixinOpenId.1", array('data' => $result , 'url' => $url));
        if(!$result['UserId'] && !$result['OpenId']){
            return '';
        }

        // 判断是企业用户还是不是企业用户 
        //  1.是使用userId转化为openid
        //  2.不是将直接返回openid
        if(!isset($result['UserId']) && isset($result['OpenId'])){
            $openid = $result['OpenId'];
        }else{
            $url = 'https://qyapi.weixin.qq.com/cgi-bin/user/convert_to_openid?access_token=' . $access_token;
            $post_data = array();
            $post_data['userid'] = $result['UserId'];

            $curl = new \Neigou\Curl();
            $curl -> time_out = 10;
            if (NEIGOU_HTTP_PROXY){
                $optConfig = array(
                    CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
                    CURLOPT_PROXY => NEIGOU_HTTP_PROXY,
                );
                $curl->SetOpt($optConfig);
                $curl->SetHeader('Content-Type', 'application/json');
            }
            $result = $curl -> Post($url,json_encode($post_data));
            \Neigou\Logger::General("wx.getQyWeixinOpenId.2", array('data' => $result ,'request' => $post_data, 'url' => $url));
            $result = json_decode($result,true);

            if($result['errcode'] != 0){
                return '';
            }
            $openid = $result['openid'];
        }
        return $openid;
    }
    	
}
