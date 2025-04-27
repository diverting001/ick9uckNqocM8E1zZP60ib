<?php

/**
 * alipay notify 异步验证接口
 * @auther wuchuanbin
 * @version 0.1
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mjbalipay_server extends ectools_payment_app {

	/**
	 * 支付后返回后处理的事件的动作
	 * @params array - 所有返回的参数，包括POST和GET
	 * @return null
	 */
    public function callback(&$recv){
        \Neigou\Logger::General('ecstore.mjbalipay.notify',array('recv'=>$recv));
        $ret['callback_source'] = 'server';
        if($this->is_return_vaild($recv,$recv['sign'])){
            $ret['payment_id'] = $recv['out_trade_no'];
            $ret['account'] = $this->getConf('mer_id', 'wap_payment_plugin_mjbalipay');
            $ret['bank'] = app::get('wap')->_('嘉宝支付宝');
            $ret['pay_account'] = $recv['buyer_email'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_fee'];
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_fee'];
            $ret['trade_no'] = $recv['trade_no'];
            $ret['t_payed'] = strtotime($recv['notify_time']) ? strtotime($recv['notify_time']) : time();
            $ret['pay_app_id'] = "mjbalipay";
            $ret['pay_type'] = 'online';
            $ret['memo'] = '';
            $status = $recv['trade_status'];        //返回token
            if($status == 'TRADE_FINISHED' || $status == 'TRADE_SUCCESS'){
                echo "success";
                $ret['status'] = 'succ';
            }else{
                echo "fail";
                $ret['status'] = 'failed';
            }
        }else{
            $ret['message'] = 'Invalid Sign';
            $ret['status'] = 'invalid';
        }
        return $ret;
    }


    /**
     * 数据签名处理
     * @param array $toBeSigned
     * @param bool $verify
     * @return bool|string
     */
    protected function getSignContent(array $toBeSigned, $verify = false)
    {
        ksort($toBeSigned);
        $stringToBeSigned = '';
        foreach ($toBeSigned as $k => $v) {
            if ($verify && $k != 'sign' && $k != 'sign_type') {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
            if (!$verify && $v !== '' && !is_null($v) && $k != 'sign' && '@' != substr($v, 0, 1)) {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
        }
        $stringToBeSigned = substr($stringToBeSigned, 0, -1);
        unset($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 检验返回数据合法性
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return boolean
     */
    public function is_return_vaild($data,$sign){
        $str = $this->getSignContent($data,true);
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($this->getConf('pub_key','wap_payment_plugin_mjbalipay'), 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        $result = (bool)openssl_verify($str, base64_decode($sign), $res);
        return $result;
    }

}
