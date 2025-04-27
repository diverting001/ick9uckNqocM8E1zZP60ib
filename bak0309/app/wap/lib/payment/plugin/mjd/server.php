<?php

/**
 * 京东支付 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mjd_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        \Neigou\Logger::General('ecstore.notify.mjd', array('remark' => 'param_init', 'data' => 111,'post_data'=>$GLOBALS["HTTP_RAW_POST_DATA"],'request_data'=>$_REQUEST));
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        $mer_id = $this->getConf('mer_id', 'wap_payment_plugin_mjd');
        if($this->is_return_vaild($GLOBALS["HTTP_RAW_POST_DATA"],$recv)){
            if($recv['status']==2){
                $ret['payment_id'] = $recv['tradeNum'];
                $ret['account'] = $mer_id;
                $ret['bank'] = app::get('ectools')->_('京东支付');
                $pay_detail = $recv['payList']['pay']['detail'];
                $pay_detail = json_decode($pay_detail,true);
                $ret['pay_account'] = $pay_detail['cardholderMobile'];
                $ret['currency'] = 'CNY';
                $ret['money'] = $recv['amount']/100;
                $ret['paycost'] = '0.000';
                $ret['cur_money'] = $recv['amount']/100;
                $ret['trade_no'] = $recv['tradeNum'];//JD 交易流水号为上报订单号 对接人称此号 在JD系统唯一
                $ret['t_payed'] = strtotime($recv['payList']['pay']['tradeTime']) ? strtotime($recv['payList']['pay']['tradeTime']) : time();
                $ret['pay_app_id'] = "mjd";
                $ret['pay_type'] = 'online';
                $ret['status'] = 'succ';
                echo 'success';
                \Neigou\Logger::General('ecstore.notify.mjd', array('remark' => 'trade_succ', 'data' => $recv));
            } else {
                $ret['status']='invalid';
                echo 'fail';
                \Neigou\Logger::General('ecstore.notify.mjd', array('remark' => 'trade_status_err', 'data' => $recv));
            }
        }else{
            echo 'fail';
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mjd',array('remark'=>'sign_err','data'=>$recv));
        }
        return $ret;

    }

    /**
     * 检验返回数据合法性
     * @param $xml
     * @param $resData
     * @return mixed
     */
    public function is_return_vaild($resultData,&$resData) {
        $resultXml = simplexml_load_string($resultData);
        $resultObj = json_decode(json_encode($resultXml),TRUE);
        $encryptStr = $resultObj["encrypt"];
        $encryptStr=base64_decode($encryptStr);
        $desKey = $this->getConf('des_key', 'wap_payment_plugin_mjd');
        $keys = base64_decode($desKey);
        $reqBody = $this->decrypt4HexStr($keys, $encryptStr);
        //echo "请求返回encrypt Des解密后:".$reqBody."\n";
        \Neigou\Logger::General('ecstore.notify.mjd',array('remark'=>'reqBody','data'=>$reqBody));
        $bodyXml = simplexml_load_string($reqBody);
        //echo "请求返回encrypt Des解密后:".$bodyXml->saveXML()."\n";
        \Neigou\Logger::General('ecstore.notify.mjd',array('remark'=>'bodyXml','data'=>$bodyXml));
        $resData = json_decode(json_encode($bodyXml),TRUE);

        $inputSign = $resData["sign"];
// 		$bodyDom = XMLUtil::arrtoxml($bodyObj,0,0);
// 		$rootDom = $bodyDom->getElementsByTagName("jdpay");
// 		$signNodelist = $rootDom[0]->getElementsByTagName("sign");
// 		$rootDom[0]->removeChild($signNodelist[0]);

// 		$reqBodyStr = XMLUtil::xmlToString($bodyDom);

        $startIndex = strpos($reqBody,"<sign>");
        $endIndex = strpos($reqBody,"</sign>");
        $xml;

        if($startIndex!=false && $endIndex!=false){
            $xmls = substr($reqBody, 0,$startIndex);
            $xmle = substr($reqBody,$endIndex+7,strlen($reqBody));
            $xml=$xmls.$xmle;
        }

        //echo "本地摘要原串:".$xml."\n";
        \Neigou\Logger::General('ecstore.notify.mjd',array('remark'=>'本地摘要原串','data'=>$xml));
        $sha256SourceSignString = hash("sha256", $xml);
        \Neigou\Logger::General('ecstore.notify.mjd',array('remark'=>'本地摘要','data'=>$sha256SourceSignString));
        //echo "本地摘要:".$sha256SourceSignString."\n";

        $decryptStr = $this->decryptByPublicKey($inputSign);
        \Neigou\Logger::General('ecstore.notify.mjd',array('remark'=>'解密后摘要','data'=>$decryptStr));
        //echo "解密后摘要:".$decryptStr."\n";
        $flag;
        if($decryptStr==$sha256SourceSignString){
            //echo "验签成功<br/>";
            \Neigou\Logger::General('ecstore.notify.mjd',array('remark'=>'sign_true'));
            $flag=true;
        }else{
            //echo "验签失败<br/>";
            \Neigou\Logger::General('ecstore.notify.mjd',array('remark'=>'sign_err'));
            $flag=false;
        }
        $resData["version"]=$resultObj["version"];
        $resData["merchant"]=$resultObj["merchant"];
        $resData["result"]=$resultObj["result"];
        \Neigou\Logger::General('ecstore.notify.mjd',array('remark'=>'resData','data'=>$resData));
        //echo var_dump($resData);
        return $flag;
    }
    function signString($data, $unSignKeyList) {
        $linkStr="";
        $isFirst=true;
        ksort($data);
        foreach($data as $key=>$value){
            if($value==null || $value==""){
                continue;
            }
            $bool=false;
            foreach ($unSignKeyList as $str) {
                if($key."" == $str.""){
                    $bool=true;
                    break;
                }
            }
            if($bool){
                continue;
            }
            if(!$isFirst){
                $linkStr.="&";
            }
            $linkStr.=$key."=".$value;
            if($isFirst){
                $isFirst=false;
            }
        }
        return $linkStr;
    }

    /**
     * 转换一个String字符串为byte数组
     * @param $string
     * @return array
     */
    function getBytes($string) {
        $bytes = array ();
        for($i = 0; $i < strlen ( $string ); $i ++) {
            $bytes [] = ord ( $string [$i] );
        }
        return $bytes;
    }

    /**
     * 转换一个int为byte数组
     * @param $val
     * @return array
     */
    function integerToBytes($val) {
        $byt = array ();
        $byt [0] = ($val >> 24 & 0xff);
        $byt [1] = ($val >> 16 & 0xff);
        $byt [2] = ($val >> 8 & 0xff);
        $byt [3] = ($val & 0xff);
        return $byt;
    }

    /**
     * 将字节数组转化为String类型的数据
     * @param $bytes
     * @return string
     */
    function toStr($bytes) {
        $str = '';
        foreach ( $bytes as $ch ) {
            $str .= chr ( $ch );
        }

        return $str;
    }

    /**
     * 将十进制字符串转换为十六进制字符串
     * @param $string
     * @return string
     */
    function strToHex($string) {
        $hex = "";
        for($i = 0; $i < strlen ( $string ); $i ++) {
            $tmp = dechex ( ord ( $string [$i] ) );
            if (strlen ( $tmp ) == 1) {
                $hex .= "0";
            }
            $hex .= $tmp;
        }
        $hex = strtolower ( $hex );
        return $hex;
    }




    function decryptByPublicKey($data) {
        $pu_key =  openssl_pkey_get_public(file_get_contents($this->getConf('pub_cert_path', 'wap_payment_plugin_mjd')));//这个函数可用来判断公钥是否是可用的，可用返回资源id Resource id
//        echo "--->".$pu_key."\n";
        $decrypted = "";
        $data = base64_decode($data);
//        echo $data."\n";

        openssl_public_decrypt($data,$decrypted,$pu_key);//公钥解密

//        echo $decrypted."\n";
        return $decrypted;
    }

    /**
     * 3DES 解密 进行了补位的16进制表示的字符串数据
     *
     * @return
     *
     */
    function decrypt4HexStr($keys, $data) {
        $hexSourceData = array ();

        $hexSourceData = $this->hexStrToBytes ($data);
        //var_dump($hexSourceData);

        // 解密
        $unDesResult = $this->decrypt ($this->toStr($hexSourceData),$keys);
        //echo $unDesResult;
        $unDesResultByte = $this->getBytes($unDesResult);
        //var_dump($unDesResultByte);
        $dataSizeByte = array ();
        for($i = 0; $i < 4; $i ++) {
            $dataSizeByte [$i] = $unDesResultByte [$i];
        }
        // 有效数据长度
        $dsb = $this->byteArrayToInt( $dataSizeByte, 0 );
        $tempData = array ();
        for($j = 0; $j < $dsb; $j++) {
            $tempData [$j] = $unDesResultByte [4 + $j];
        }

        return $this->hexTobin ($this->bytesToHex ( $tempData ));

    }
    /**
     *
     *
     *
     *
     * 转换一个16进制hexString字符串为十进制byte数组
     *
     * @param $hexString 需要转换的十六进制字符串
     * @return 一个byte数组
     *
     */
    function hexStrToBytes($hexString) {
        $bytes = array ();
        for($i = 0; $i < strlen ( $hexString ) - 1; $i += 2) {
            $bytes [$i / 2] = hexdec ( $hexString [$i] . $hexString [$i + 1] ) & 0xff;
        }

        return $bytes;
    }
    function decrypt($encrypted, $key) {
        //$encrypted = base64_decode($encrypted);
        $td = mcrypt_module_open ( MCRYPT_3DES, '', 'ecb', '' ); // 使用MCRYPT_DES算法,cbc模式
        $iv = @mcrypt_create_iv ( mcrypt_enc_get_iv_size ( $td ), MCRYPT_RAND );
        $ks = mcrypt_enc_get_key_size ( $td );
        @mcrypt_generic_init ( $td, $key, $iv ); // 初始处理
        $decrypted = mdecrypt_generic ( $td, $encrypted ); // 解密
        mcrypt_generic_deinit ( $td ); // 结束
        mcrypt_module_close ( $td );
        //$y = TDESUtil::pkcs5Unpad ( $decrypted );
        return $decrypted;
    }
    /**
     * 将byte数组 转换为int
     *
     * @param
     *        	b
     * @param
     *        	offset 位游方式
     * @return
     *
     *
     */
    function byteArrayToInt($b, $offset) {
        $value = 0;
        for($i = 0; $i < 4; $i ++) {
            $shift = (4 - 1 - $i) * 8;
            $value = $value + ($b [$i + $offset] & 0x000000FF) << $shift; // 往高位游
        }
        return $value;
    }

    /**
     *
     * @param unknown $hexstr
     * @return Ambigous <string, unknown>
     */
    function hexTobin($hexstr)
    {
        $n = strlen($hexstr);
        $sbin="";
        $i=0;
        while($i<$n)
        {
            $a =substr($hexstr,$i,2);
            $c = pack("H*",$a);
            if ($i==0){$sbin=$c;}
            else {$sbin.=$c;}
            $i+=2;
        }
        return $sbin;
    }
    // 字符串转16进制
    function bytesToHex($bytes) {
        $str = $this->toStr ( $bytes );
        return $this->strToHex ( $str );
    }

}