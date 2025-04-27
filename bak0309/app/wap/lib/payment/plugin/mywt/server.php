<?php

/**
 * 一网通 notify 验证接口
 * User: chuanbin
 * Date: 2018/1/2
 * Time: 14:16
 */
class wap_payment_plugin_mywt_server extends ectools_payment_app
{

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv)
    {
        $recv = json_decode($_POST['jsonRequestData'], true);
        $check_sign = $this->verifySign($recv['noticeData'], $recv['sign'], $this->getPubKey());
        if ($check_sign !== true) {
            \Neigou\Logger::General('notify.mywt.callback.err', array('remark' => 'sign_err', 'data' => $_POST));
            header('HTTP/1.1 404 Not Found');
            header("status: 404 Not Found");
            $ret['status'] = 'invalid';
            return $ret;
        }
        $recv = $recv['noticeData'];
        $ret['callback_source'] = 'server';
        //检测payment_id对应的支付单号是否已经超过40min
        $payment_info = app::get('ectools')->model('payments')->getRow('*', array('payment_id' => $recv['orderNo']));
        $time_spend = time() - $payment_info['t_begin'];
        if ($time_spend > 2400) {
            \Neigou\Logger::General('notify.mywt.callback.err', array('remark' => 'timeout', 'data' => $_POST));
            header('HTTP/1.1 404 Not Found');
            header("status: 404 Not Found");
            $ret['status'] = 'invalid';
            return $ret;
        }
        $ret['bank'] = app::get('ectools')->_('一网通支付');
        $ret['currency'] = 'CNY';
        $ret['payment_id'] = $recv['orderNo'];
        $ret['paycost'] = '0.000'; // 手续费
        $ret['trade_no'] = $recv['bankSerialNo'];
        $ret['money'] = $recv['amount'];
        $ret['cur_money'] = $recv['amount'];
        $ret['t_payed'] = strtotime($recv['dateTime']);
        $ret['pay_app_id'] = 'mywt';
        $ret['pay_type'] = 'online';
        $ret['status'] = 'succ';
        $obj_order_bills = app::get('ectools')->model('order_bills');
        $order_bill_info = $obj_order_bills->getRow('*', array('bill_id' => $recv['orderNo']));
        $order_info = kernel::single("b2c_service_order")->getOrderInfo($order_bill_info['rel_id']);
        if ($order_info['point_amount'] == 0) {
            /* @var b2c_service_xinfutongorder $lib_xinfutong */
            $lib_xinfutong = kernel::single('b2c_service_xinfutongorder');
            $lib_xinfutong->confirmPoint($order_info);
        }
        \Neigou\Logger::General('notify.mywt.callback', array('remark' => 'succ', 'data' => $ret));
        return $ret;
    }

    private function getPubKey()
    {
        $config = app::get('ectools')->getConf('wap_payment_plugin_mywt');
        $config = unserialize($config);
        $config = $config['setting'];
        $url = $config['api_public_key'];
        $curl = new \Neigou\Curl();
        $pars = array(
            'version' => '1.0',
            'charset' => 'UTF-8',
            'signType' => 'SHA-256',
            'reqData' => array(
                'dateTime' => date('YmdHis', time()),
                'txCode' => 'FBPK',
                'branchNo' => $config['branchNo'],
                'merchantNo' => $config['merchantNo'],
            ),
        );
        $pars['sign'] = kernel::single('wap_payment_plugin_mywt')->sign($pars['reqData']);
        $req = array(
            'jsonRequestData' => json_encode($pars)
        );
        $result = $curl->Post($url, $req);
        $result = json_decode($result, true);
        return $result['rspData']['fbPubKey'];
    }

    //校验 sha1WithRSA 签名
    function verifySign($data, $sign, $pubKey)
    {
        ksort($data);
        $data = urldecode(http_build_query($data));
        $sign = base64_decode($sign);
        $pubKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        $key = openssl_pkey_get_public($pubKey);
        $result = openssl_verify($data, $sign, $key, OPENSSL_ALGO_SHA1) === 1;
        return $result;
    }
}