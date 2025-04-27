<?php

class wap_payment_plugin_mpufapay_server extends ectools_payment_app
{
    public $supportCurrency = array("CNY"=>"01");
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mpufapay';

    private $prefix = 'wap_payment_plugin_mpufapay_prefix';

	/**
	 * 支付后返回后处理的事件的动作
	 * @params array - 所有返回的参数，包括POST和GET
	 * @return null
	 */
    public function callback(&$recv)
    {
        $this->log($this->prefix . 'notifyTrace', array(
            'recv' => $recv,
            'raw' => $GLOBALS["HTTP_RAW_POST_DATA"]
        ));

        $data = json_decode($GLOBALS["HTTP_RAW_POST_DATA"], true) ?: array();

        $ret['callback_source'] = 'server';
        //键名与pay_setting中设置的一致
        if ($this->checkSign($data)) {
            $this->log($this->prefix . 'signOk', compact('ret', 'data'));
            $ret['payment_id'] = $data['termSsn'];
            $ret['account'] = app::get('wap')->_('小浦支付');
            $ret['bank'] = app::get('wap')->_('小浦支付');
            $ret['pay_account'] = app::get('wap')->_('付款帐号');
            $ret['currency'] = 'CNY';
            $ret['trade_no'] = $data['tranNo'];
            $ret['t_payed'] = strtotime($data['successTime']);
            $ret['pay_app_id'] = 'mpufapay';
            $ret['pay_type'] = 'online';
            $ret['memo'] = '';
            $status = $data['status'];        //返回token
            if ($status == '00') {
                $ret['status'] = 'succ';
            } elseif ($status == '01') {
                $ret['status'] = 'progress';
            } else {
                $ret['status'] = 'failed';
            }
        } else {
            $this->log($this->prefix . 'signFail', compact('ret', 'data'));
            $ret['message'] = 'Invalid Sign';
            $ret['status'] = 'invalid';
        }
        return $ret;
    }

    private function getSign($params)
    {
        $params['secretKey'] = $this->getSecretKey();
        if (isset($params['transName'])) {
            unset($params['transName']);
        }
        ksort($params);
        $pieces = array();
        foreach ($params as $key => $value) {
            $pieces[] = $key . '=' . $value;
        }
        return md5(implode('&', $pieces));
    }

    private function checkSign($recv)
    {
        $sign = $recv['sign'];
        unset($recv['sign']);
        $newSign = $this->getSign($recv);
    
        if ($sign == $newSign) {
            return true;
        } else {
            $this->log($this->prefix . 'signFail', compact('sign', 'newSign'));
            return false;
        }
    }

    private function getSecretKey()
    {
        return $this->getConf('secret_key', 'wap_payment_plugin_mpufapay');
    }

    public function getBaseUrl()
    {
        return $this->getConf('base_url', 'wap_payment_plugin_mpufapay');
    }

    private function getRefundUrl()
    {
        return $this->getConf('refund_url', 'wap_payment_plugin_mpufapay');
    }

    private function log($name, $params)
    {
        \Neigou\Logger::General($name, $params);
    }
}
