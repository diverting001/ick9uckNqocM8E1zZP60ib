<?php

/**
 * 发福利支付 notify 验证接口
 * User: chuanbin
 * Date: 2018/1/2
 * Time: 14:16
 */
class wap_payment_plugin_mfafuli_server extends ectools_payment_app
{

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv)
    {
        $raw = @file_get_contents('php://input');
        $recv = json_decode($raw, true);
        header('Content-Type:text/html; charset=utf-8');
        \Neigou\Logger::General('notify.mfafuli.callback', array('raw' => $raw));
        if (!$this->check_sign($recv)) {
            echo 'fail';
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('notify.mfafuli.callback.err', array('remark' => 'sign_err', 'data' => $recv));
            return $ret;
        }
        if ($recv['orderStatus'] !== '011') {
            $ret['status'] = 'invalid';
            echo 'fail';
            \Neigou\Logger::General('notify.mfafuli.callback.err', array('remark' => 'orderStatus err', 'data' => $recv));
            return $ret;
        }
        $ret['callback_source'] = 'server';
        //检测payment_id对应的支付单号是否已经超过40min
        $t_begin = app::get('ectools')->model('payments')->getRow('t_begin', array('payment_id' => $recv['merchantOrderNo']));
        $time_spend = time() - $t_begin['t_begin'];
        if ($time_spend > 2400) {
            $ret['status'] = 'invalid';
            echo 'fail';
            \Neigou\Logger::General('ecstore.notify.mfafuli', array('remark' => 'pay timeout', 'data' => $recv));
            return $ret;
        }
        $ret['bank'] = app::get('ectools')->_('发福利支付');
        $ret['currency'] = 'CNY';
        $ret['payment_id'] = $recv['merchantOrderNo'];
        $ret['paycost'] = '0.000';
//        $ret['account'] = $recv['account'];
//        $ret['pay_account'] = $recv['account'];
        $ret['trade_no'] = $recv['orderNo'];
        $ret['money'] = $recv['orderPoint'];
        $ret['cur_money'] = $recv['orderPoint'];
        $ret['t_payed'] = strtotime($recv['payDate']);
        $ret['pay_app_id'] = "mfafuli";
        $ret['pay_type'] = 'online';
        $ret['status'] = 'succ';
        echo 'success';
        \Neigou\Logger::General('ecstore.notify.mfafuli', array('remark' => 'trade_succ', 'data' => $ret));
        return $ret;
    }

    /**
     * 检验返回数据合法性
     * @param $param
     * @return mixed
     */
    private function check_sign($pars)
    {
        $sign = $pars['sign'];
        unset($pars['sign']);
        ksort($pars);
        $sign_str = urldecode(http_build_query($pars));
        $sign_local = sha1($sign_str . $this->getConf('app_key', 'wap_payment_plugin_mfafuli'), false);
        if ($sign == $sign_local) {
            return true;
        } else {
            \Neigou\Logger::General('notify.mfafuli.sign.err', array('linkS' => $sign_str, 'sign' => $sign_local, 'sign_req' => $sign));
            return false;
        }

    }
}