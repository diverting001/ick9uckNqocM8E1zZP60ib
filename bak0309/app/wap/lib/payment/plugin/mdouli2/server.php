<?php

/**
 * 兜礼支付 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mdouli2_server extends ectools_payment_app
{

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv)
    {
        $config = app::get('ectools')->getConf('wap_payment_plugin_mdouli2');
        $config = unserialize($config);
        $this->_config = $config['setting'];

        header('Content-Type:text/html; charset=utf-8');
        $raw = @file_get_contents('php://input');
        \Neigou\Logger::General('notify.mdouli2.callback', array('raw' => $raw));
        $recv = json_decode($raw, true);

        if (!$this->is_return_vaild($recv)) {
            \Neigou\Logger::General('notify.mdouli2.callback.err', array('code' => 500));
            echo json_encode(array(
                'code' => '500',
                'info' => '签名错误',
            ));
            die;
        }
        $recv = json_decode($recv['param'], true);
        if ($recv['payStatus'] != 1) {
            \Neigou\Logger::General('notify.mdouli2.callback.err', array('payStatus' => $recv['payStatus']));
            echo json_encode(array(
                'code' => '500',
                'info' => '未支付状态',
            ));
            die;
        }

        $ret['callback_source'] = 'server';
        $ret['payment_id'] = $recv['merchantOrderNo'];
        $ret['bank'] = app::get('ectools')->_('兜礼支付2');
        $ret['currency'] = 'CNY';
        $ret['money'] = $recv['orderAmount'];
//        $ret['account'] = $mer_id;
//        $ret['pay_account'] = $recv['customNo'];
        $ret['paycost'] = '0.000';
        $ret['cur_money'] = $recv['orderAmount'];
        $ret['trade_no'] = $recv['outTradeNo'];//queryId 交易流水号 traceNo 系统跟踪号
        $ret['t_payed'] = time();
        $ret['pay_app_id'] = "mdouli2";
        $ret['pay_type'] = 'online';
        $ret['status'] = 'succ';
        echo json_encode(array(
            'code' => '1000',
            'info' => '',
        ));
        \Neigou\Logger::General('notify.mdouli2.callback.ok', array('$ret' => $ret));
        $this->cancel_invalid_pays($recv['merchantOrderNo']);
        return $ret;
    }

    function cancel_invalid_pays($payment_id)
    {
        $mdouli2 = kernel::single('wap_payment_plugin_mdouli2');
        $config = app::get('ectools')->getConf('wap_payment_plugin_mdouli2');
        $config = unserialize($config);
        $this->_config = $config['setting'];

        //获取订单编号
        $order_info = app::get('ectools')->model('order_bills')->getRow('rel_id', array('bill_id' => $payment_id));

        $bills_info_list = app::get('ectools')->model('order_bills')->getList('bill_id', array('rel_id' => $order_info['rel_id']));
        foreach ($bills_info_list as $bills_info) {
            if ($bills_info['bill_id'] == $payment_id) {
                continue;
            }
            $payments_info = app::get('ectools')->model('payments')->getRow('*', array('payment_id' => $bills_info['bill_id']));
            $param = array(
                'businessId' => $this->_config['businessId'],
                'merchantOrderNo' => $payments_info['payment_id'],
                'payId' => $payments_info['pay_account'],
            );
            $ret = $mdouli2->request('payment/pay/cancelOrder', $param);
        }
    }

    function sign($pars)
    {
        ksort($pars);
        $sign_str = '';
        foreach ($pars as $kk => $vv) {
            $sign_str .= $kk . '=' . $vv . '&';
        }
        $sign_str = $sign_str . 'client_secret=' . $this->_config['client_secret'];
//        echo $sign_str;
        $sign = md5($sign_str);
        return $sign;
    }

    /**
     * 检验返回数据合法性
     * @param mixed $params 包含签名数据的数组
     * @access private
     * @return boolean
     */
    public function is_return_vaild($params)
    {
        $signature_str = $params ['sign'];
        unset ($params ['sign']);
        $sign = $this->sign($params);
//        echo $sign."\n".$signature_str;
        if ($sign == $signature_str) {
            return true;
        } else {
            return false;
        }
    }
}