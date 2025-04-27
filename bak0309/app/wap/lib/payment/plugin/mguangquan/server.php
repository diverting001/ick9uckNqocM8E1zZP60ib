<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

/**
 * alipay notify 异步验证接口
 * @auther shopex ecstore dev dev@shopex.cn
 * @version 0.1
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mguangquan_server extends ectools_payment_app {
    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv){
        \Neigou\Logger::General('ecstore.notify.mguangquan', array('remark' => 'param_init','request_data'=>$_REQUEST));
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';

        $mer_id = $this->getConf('seller_id', 'wap_payment_plugin_malipay2');
        if($this->is_return_vaild($recv,'RSA2')){

            if($recv['trade_status'] == 'TRADE_FINISHED' || $recv['trade_status'] == 'TRADE_SUCCESS'){
                $ret['payment_id'] = $recv['out_trade_no'];
                $ret['account'] = $mer_id;
                $ret['bank'] = app::get('ectools')->_('光圈支付');
                $ret['pay_account'] = app::get('wap')->_('付款帐号');
                $ret['currency'] = 'CNY';
                $ret['money'] = $recv['total_amount'];
                $ret['paycost'] = '0.000';
                $ret['cur_money'] = $recv['total_amount'];
                $ret['trade_no'] = $recv['trade_no'];
                $ret['t_payed'] = strtotime($recv['notify_time']) ? strtotime($recv['notify_time']) : time();
                $ret['pay_app_id'] = "mguangquan";
                $ret['pay_type'] = 'online';
                $ret['status'] = 'succ';
                echo 'success';
                \Neigou\Logger::General('ecstore.notify.mguangquan', array('remark' => 'trade_succ', 'data' => $recv));
            } else {
                $ret['status']='invalid';
                echo 'fail';
                \Neigou\Logger::General('ecstore.notify.mguangquan', array('remark' => 'trade_status_err', 'data' => $recv));
            }
        }else{
            echo 'fail';
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mguangquan',array('remark'=>'sign_err','data'=>$recv));
        }
        return $ret;
    }

    public function getSignContent($params) {
        ksort($params);

        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $stringToBeSigned;
    }


    /** rsaCheckV1 & rsaCheckV2
     *  验证签名
     **/
    public function is_return_vaild($params,$signType='RSA') {
        $sign = $params['sign'];
        $params['sign_type'] = null;
        $params['sign'] = null;
        $ret = $this->verify($this->getSignContent($params), $sign,$signType);
        if(!$ret){
            \Neigou\Logger::General('ecstore.notify.mguangquan', array('action' => 'trade_start', 'data' => json_encode($params),'data2'=>$signType,'sparam1'=>json_encode($this->zfb_public_key)));
        }
        return $ret;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    function verify($data, $sign, $signType = 'RSA') {
        $pubKey= $this->getConf('zfb_public_key', 'wap_payment_plugin_malipay2');
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        if(!$res){
            return false;
        }
        if ("RSA2" == $signType) {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res, 'sha256');
        } else {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        }
        return $result;
    }
}