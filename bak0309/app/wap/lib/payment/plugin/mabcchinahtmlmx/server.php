<?php
/**
 * 农业银行支付 notify 验证接口
 * User: chuanbin
 * Date: 2018/1/24
 * Time: 10:31
 */
class wap_payment_plugin_mabcchinahtmlmx_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        \Neigou\Logger::General('notify.mabcchinahtmlmx.req', array('remark' => '异步通知开始', 'post_data'=>$recv));
        $aMessage = $recv['MSG'];
        //1、还原经过base64编码的信息
        \Neigou\Logger::General('notify.mabcchinahtmlmx.req', array('remark' => '异步通知aMessage', 'post_data'=>$aMessage));
        $tMessage = base64_decode(trim($aMessage));
        //2、取得经过签名验证的报文
        \Neigou\Logger::General('notify.mabcchinahtmlmx.req', array('remark' => '解析base64', 'post_data'=>iconv('GB2312','UTF-8',$tMessage)));
        $mer_id = $this->getConf('mer_id', 'wap_payment_plugin_mabcchinahtmlmx');
        if($this->is_return_vaild($tMessage)){
            \Neigou\Logger::General('notify.mabcchinahtmlmx.req', array('remark' => '交易签名认证通过', 'post_data'=>1));
            $ret['payment_id'] = $this->getValueXml('OrderNo',$tMessage);
            \Neigou\Logger::General('notify.mabcchinahtmlmx.tmp', array('remark' => '交易细节', 'payment_id'=>$ret['payment_id']));
            $ret['account'] = $mer_id;
            $ret['bank'] = app::get('ectools')->_('农行H5免息支付');
            $ret['pay_account'] = $mer_id;
            $ret['currency'] = 'CNY';
            $ret['money'] = $this->getValueXml('Amount',$tMessage);
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $this->getValueXml('Amount',$tMessage);
            $ret['trade_no'] = $this->getValueXml('iRspRef',$tMessage);//JD 交易流水号为上报订单号 对接人称此号 在JD系统唯一
            $ret['t_payed'] = time();
            $ret['pay_app_id'] = "mabcchinahtmlmx";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            $URL = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mabcchinahtmlmx', 'callback');
            $URL .= '?sn='.$ret['payment_id'];
            print ("<html><head><meta http-equiv=\"refresh\" content=\"0; url='{$URL}'\"></head></html>");
            \Neigou\Logger::General('ecstore.notify.mabcchinahtmlmx', array('remark' => 'trade_succ', 'data' => $ret));
        }else{
            echo 'sign_err';
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mabcchinahtmlmx',array('remark'=>'sign_err','data'=>$recv));
        }
        return $ret;

    }



    public function getValueXml($aTag,$iXMLString)
    {
        $tXMLDocument = null;
        $tStartIndex = strpos($iXMLString, '<'.trim($aTag).'>');
        $tEndIndex = strpos($iXMLString, '</'.trim($aTag).'>');
        if (($tStartIndex !== FALSE) && ($tEndIndex !== FALSE) && ($tStartIndex < $tEndIndex))
        {
            $tXMLDocument = substr($iXMLString, $tStartIndex + strlen($aTag) + 2, $tEndIndex - ($tStartIndex + strlen($aTag) + 2));
        }
        return $tXMLDocument;
    }

    /**
     * 退款通知
     */
    public function query(){
        //发起交易查询
        $field['TrxType'] = 'Query';
        $field['PayTypeID'] = 'ImmediatePay';
        $field['OrderNo'] = '15167620166808';
        $field['QueryDetail'] = '1';
        $req_msg = json_encode($field);
        $tSignature = $this->genSignature($req_msg);
        $url = 'https://pay.abchina.com/ebus/trustpay/ReceiveMerchantTrxReqServlet';
        $res = $this->request($url,$tSignature);
        \Neigou\Logger::General('ecstore.notify.mabcchinahtmlmx',array('remark'=>'请求参数','field'=>json_encode($field),'tSignature'=>$tSignature,'response_data'=>$res));
        if($this->is_return_vaild($res)){
            //获取订单详细信息
            $order = $this->GetValue('Order',$res);
            $str =  base64_decode($order);
            $str = iconv('GB2312','UTF-8',$str);
            $rzt = json_decode($str,true);
            print_r($rzt);
        }
    }
    private function der2pem($der_data) {
        $pem = chunk_split(base64_encode($der_data), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
        return $pem;
    }


    public function GetValue($aTag,$json)
    {
        $index = 0;
        $length = 0;
        $index = strpos($json, $aTag, 0);
        if ($index === false)
            return "";
        do
        {
            if($json[$index-1] === "\"" && $json[$index+strlen($aTag)] === "\"")
            {
                break;
            }
            else
            {
                $index = strpos($json, $aTag, $index+1);
                if ($index === false)
                    return "";
            }
        } while (true);
        $index = $index + strlen($aTag) + 2;
        $c = $json[$index];
        if ($c === '{')
        {
            $output = $this->GetObjectValue($index, $json);
        }
        if ($c === '"')
        {
            $output = $this->GetStringValue($index, $json);
        }
        return $output;
    }

    /// <summary> 回传文件中的信息
    /// </summary>
    /// <param name="aTag">域名
    /// </param>
    /// <returns> 指定域的object值
    ///
    /// </returns>
    private function GetObjectValue($index, $json)
    {
        $count = 0;
        $_output = "";
        do
        {
            $c = $json[$index];
            if ($c === '{')
            {
                $count++;
            }
            if ($c === '}')
                $count--;

            if ($count !== 0)
            {
                $_output =$_output.$c;
            }
            else
            {
                $_output = $_output.$c;
                return $_output;
            }
            $index++;
        } while (true);
    }
    /// <summary> 回传文件中的信息
    /// </summary>
    /// <param name="aTag">域名
    /// </param>
    /// <returns> 指定域的值
    ///
    /// </returns>
    private function GetStringValue($index, $json)
    {
        $index++;
        $_output = "";
        do
        {
            $c = $json[$index++];
            if ($c !== '"')
            {
                $_output = $_output.$c;
            }
            else
            {
                return $_output;
            }

        } while (true);
    }


    /**
     * 组成完整交易报文
     * @param aMessage |交易报文
     * @throws TrxException：报文内容不合法
     * @return 完整交易报文
     */
    private function composeRequestMessage($aMessage) {
        $tMessage = "{\"Version\":\"V3.0.0\",\"Format\":\"JSON\",\"Merchant\":" . "{\"ECMerchantType\":\"" . "EBUS" . "\",\"MerchantID\":\"" . $this->getConf('mer_id','wap_payment_plugin_mabcchinahtmlmx') . "\"}," . "\"TrxRequest\":" . $aMessage . "}";
        return $tMessage;
    }

    public function signMessage($aMessage) {
        //1、读取商户证书
        $tMerchantCertFile = $this->getConf('pfx_file','wap_payment_plugin_mabcchinahtmlmx');
//        echo $tMerchantCertFile;die;
        $tMerchantCertPassword = $this->getConf('pfx_file_pass','wap_payment_plugin_mabcchinahtmlmx');
        //2、读取证书
        if (openssl_pkcs12_read(file_get_contents($tMerchantCertFile), $tCertificate, $tMerchantCertPassword)) {
            //3、验证证书是否在有效期内
            $cer = openssl_x509_parse($tCertificate['cert']);
            $t = time();
            if ($t < $cer['validFrom_time_t'] || $t > $cer['validTo_time_t']) {
                \Neigou\Logger::General('sign.mabcchinahtmlmx.pfx_file', array('remark' => 'pfx证书过期'));
            }
            //4、取得密钥
            $pkey = openssl_pkey_get_private($tCertificate['pkey']);
            if (!$pkey) {
                \Neigou\Logger::General('sign.mabcchinahtmlmx.pfx_file', array('remark' => '无法生成私钥证书对象'));
            }
        } else {
            \Neigou\Logger::General('sign.mabcchinahtmlmx.pfx_file', array('remark' => '证书读取失败'));
        }
        $signature = '';
        $data = strval($aMessage);
        if (!openssl_sign($data, $signature, $pkey, OPENSSL_ALGO_SHA1)) {
            return null;
        }

        $signature = base64_encode($signature);
        $tMessage = "{\"Message\":$data" . "," . '"Signature-Algorithm":' . '"SHA1withRSA","Signature":"' . $signature . '"}';
        return $tMessage;
    }

    public function genSignature($tRequestMessage) {
        $tRequestMessage = $this->composeRequestMessage($tRequestMessage);
//        echo $tRequestMessage;die;
        //4、对交易报文进行签名
        $sign_str = $this->signMessage($tRequestMessage);
        \Neigou\Logger::General('pay.mabcchinahtmlmx',array('remark'=>'交易报文','req_str'=>$tRequestMessage,'sign_str'=>$sign_str));
        return $sign_str;
    }

    /**
     * 获取请求消息
     * @param $obj | req消息
     * @return string
     */
    public function getRequestMessage($obj) {
        $js = '"Order":' . (json_encode(($obj->order)));
        $js = substr($js, 0, -1);
        $js = $js . ',"OrderItems":[';
        $count = count($obj->orderitems, COUNT_NORMAL);
        for ($i = 0; $i < $count; $i++) {
            $js = $js . json_encode($obj->orderitems[$i]);
            if ($i < $count -1) {
                $js = $js . ',';
            }
        }
        $js = $js . ']}}';
        $tMessage = json_encode($obj->request);
        $tMessage = substr($tMessage, 0, -1);
        $tMessage = $tMessage . ',' . $js;
        return $tMessage;
    }

    /// 发送交易报文至网上支付平台
    private function request($tURL,$aMessage)
    {
        //组成<MSG>段
        $tMessage = strval($aMessage);
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'user_agent' => 'TrustPayClient V3.0.0',
                'protocol_version' => 1.0,
                'header' => array('Content-Type: text/html', 'Accept: */*'),
                'content' => $tMessage
            ),
            'ssl' => array(
                'verify_peer' => false
            )
        );
        $context = stream_context_create($opts);
        $tResponseMessage = file_get_contents($tURL, false, $context);
        return $tResponseMessage;

    }



    /**
     * 检验返回数据合法性
     * @param $param
     * @return bool
     * @throws TrxException
     */
    public function is_return_vaild($param) {
        $tTrxResponse = $this->getValueXml('Message',$param);
        $tSignBase64 = $this->getValueXml('Signature',$param);
        $tSign = base64_decode($tSignBase64);
        $iTrustpayCertificate = openssl_x509_read($this->der2pem(file_get_contents($this->getConf('trustPay_file','wap_payment_plugin_mabcchinahtmlmx'))));
        $key = openssl_pkey_get_public($iTrustpayCertificate);
        $data = strval($tTrxResponse);
        if (openssl_verify($data, $tSign, $key, OPENSSL_ALGO_SHA1) == 1) {
            return true;
        } else {
            return false;
        }
    }
}