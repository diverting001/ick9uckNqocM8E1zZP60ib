<?php

/**
 * 银联支付 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_munionpay_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        \Neigou\Logger::General('ecstore.ectools.munionpay',
            array(
                "pay_method"=>"munionpay",
                "trade_no" => $recv['orderId'],
                "from"=>"notify_url",
                "platform"=>"wap",
                "remark"=>$recv
            ));
        #键名与pay_setting中设置的一致
        $mer_id = $this->getConf('mer_id', 'wap_payment_plugin_munionpay');
        $mer_id = $mer_id == '' ? '777290058150258' : $mer_id;
        if($this->is_return_vaild($recv)){
            $ret['payment_id'] = $recv['orderId'];
            $ret['account'] = $mer_id;
            $ret['bank'] = app::get('ectools')->_('WAP银联');//TODO 这里是支付方式名称 确认
            $ret['pay_account'] = $recv['accNo'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['settleAmt']/100;//TODO 确认是否是这个字段 清算金额
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['txnAmt']/100;
            $ret['trade_no'] = $recv['queryId'];//queryId 交易流水号 traceNo 系统跟踪号
            $ret['t_payed'] = strtotime($recv['traceTime']) ? strtotime($recv['traceTime']) : time();
            $ret['pay_app_id'] = "unionpay";
            $ret['pay_type'] = 'online';
            //$ret['memo'] = $recv['body'];
            if(intval($recv['respCode'])<1) {
                if($recv['respMsg']=='成功[0000000]'){
                    \Neigou\Logger::General('ecstore.ectools.munionpay',
                        array(
                            "trade_no" => $recv['orderId'],
                            "from"=>"notify_url",
                            "platform"=>"web",
                            "remark"=>"succ",

                        ));
                    echo '验签成功';
                    $ret['status'] = 'succ';
                } else {
                    \Neigou\Logger::General('ecstore.ectools.munionpay',
                        array(
                            "trade_no" => $recv['orderId'],
                            "from"=>"notify_url",
                            "platform"=>"web",
                            "remark"=>"respCode err",
                        ));
                    echo '验签失败';
                    $ret['status'] = 'failed';
                }
            }else{
                $ret['status'] =  'failed';
            }
        }else{
            \Neigou\Logger::General('ecstore.ectools.munionpay',
                array(
                    "trade_no" => $recv['orderId'],
                    "from"=>"notify_url",
                    "platform"=>"web",
                    "remark"=>"sign err",

                ));
            echo '签名为空';
            $message = 'Invalid Sign';
            $ret['status'] = 'invalid';
        }
        return $ret;
    }


    /**
     * 检验返回数据合法性
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return boolean
     */
    public function is_return_vaild($params) {
        $signature_str = $params ['signature'];
        unset ( $params ['signature'] );
        $params_str = $this->_create_link_string ( $params, true, false );
        $strCert = $params['signPubKeyCert'];
        openssl_x509_read($strCert);
        $certInfo = openssl_x509_parse($strCert);
        $cn = $certInfo['subject'];
        $cn = $cn['CN'];
        $company = explode('@',$cn);
        if(count($company) < 3) {
//            echo 4;
            return null;
        }
        $cn = $company[2];
        if($cn!='中国银联股份有限公司' && "00040000:SIGN" != $cn) {
            \Neigou\Logger::General('ecstore.ectools.munionpay',
                array(
                    "remark"=>"cert owner err line 103",

                ));
            return false;//cert owner is not cup $cn
        }
        $from = date_create ( '@' . $certInfo ['validFrom_time_t'] );
        $to = date_create ( '@' . $certInfo ['validTo_time_t'] );
        $now = date_create ( date ( 'Ymd' ) );
        $interval1 = $from->diff ( $now );
        $interval2 = $now->diff ( $to );
        if ($interval1->invert || $interval2->invert) {
            return null;
        }
        $result = openssl_x509_checkpurpose($strCert, X509_PURPOSE_ANY, array($this->getConf('root_cert', 'wap_payment_plugin_munionpay'), $this->getConf('middle_cert', 'wap_payment_plugin_munionpay')));

        if($result === FALSE){
            return null;
        } else if($result === TRUE){
            $params_sha256x16 = hash('sha256', $params_str);
            $signature = base64_decode ( $signature_str );
            $isSuccess = openssl_verify ( $params_sha256x16, $signature,$strCert, "sha256" );
            if($isSuccess){
                return true;
            } else {
                return false;
            }
        } else {
            return null;
        }
        return false;
    }

    /**
     * 对数组排序
     *
     * @param $para 排序前的数组
     *        	return 排序后的数组
     */
    function argSort($para) {
        ksort ( $para );
        reset ( $para );
        return $para;
    }

    /**
     * 【新】将数组转换成String
     * @return string
     */
    public function _create_link_string($para, $sort, $encode){
        if($para == NULL || !is_array($para))
            return "";
        $linkString = "";
        if($sort){
            $para = $this->argSort ( $para );
        }
        while ( list ( $key, $value ) = each( $para ) ) {
            if ($encode) {
                $value = urlencode ( $value );
            }
            $linkString .= $key . "=" . $value . "&";
        }

        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
        return $linkString;
    }



}
