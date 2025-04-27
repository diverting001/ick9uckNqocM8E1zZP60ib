<?php

/**
 * 贝那支付 notify 验证接口
 */
class wap_payment_plugin_mbenefit_server extends ectools_payment_app
{
    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback( &$recv )
    {
        $raw = file_get_contents("php://input");
        $raw = json_decode($raw,true);

        $error = '';
        $valid = $this->is_return_vaild( $raw, $error );

        \Neigou\Logger::General( 'mbenefit', array( 'action' => 'notify_callback.init', 'data' => $raw, 'remark' => $valid, 'reason' => $error ) );

        if ( $valid['status'] === true )
        {
            $receive = $valid['data'];

            $ret['payment_id'] = $receive['out_payment_no'];//payment_id
            $ret['account'] = $receive['app_id'];
            $ret['bank'] = app::get( 'ectools' )->_( '贝那支付' );
            $ret['pay_account'] = $receive['app_id'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $receive['total_fee'] / 100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $receive['total_fee'] / 100;
            $ret['trade_no'] = $receive['transaction_id'];//贝那订单号
            $ret['t_payed'] = strtotime( $receive['time_end'] );
            $ret['pay_app_id'] = 'mbenefit';
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';

            \Neigou\Logger::General( 'mbenefit', array( 'action' => 'notify_callback.success', 'data' => $recv, 'remark' => $ret ) );

            $json = '{"code":"SUCCESS","message":"OK"}';
            $this->msg( $json );
        }
        else
        {
            $ret['status'] = 'invalid';
            \Neigou\Logger::General( 'mbenefit', array( 'action' => 'notify_callback.sign err', 'data' => $recv ) );
        }

        $ret['callback_source'] = 'server';

        return $ret;
    }

    /**
     * 响应输出
     * @param $bool
     */
    private function msg( $msg )
    {
        echo $msg;
    }

    /**
     * 检验返回数据合法性
     * @param string $recv
     * @param string $error
     * @return bool
     */
    public function is_return_vaild( $recv, &$error = '' )
    {
        //验签
        $check = $this->checkSign( $recv );
        if ( $check === false )
        {
            $return['status'] = false;
            $error = 'sign error';
            return $return;
        }

        if ( $recv['app_id'] != $this->getConf( 'app_id', 'wap_payment_plugin_mbenefit' ) )
        {
            $return['status'] = false;
            $error = 'app_id error';
            return $return;
        }

        if ( $recv['trade_state'] !== 'SUCCESS' )
        {
            $return['status'] = false;
            $error = 'trade_state error';
            return $return;
        }

        $return['data'] = $recv;
        $return['status'] = true;
        return $return;
    }

    private function checkSign( $data = array() )
    {
        if ( empty( $data ) )
        {
            return false;
        }

        $sign = $data['sign'];

        unset( $data['sign'] );

        $data = array_filter( $data );

        ksort( $data );

        $buildString = "";
        foreach ( $data as $key => $value )
        {
            $buildString .= $key . "=" . $value . '&';
        }

        $tempSign = $buildString . 'key=' . $this->getConf( 'benefit_key', 'wap_payment_plugin_mbenefit' );

        return (strtoupper( md5( $tempSign ) ) === $sign) ? true : false;
    }

}