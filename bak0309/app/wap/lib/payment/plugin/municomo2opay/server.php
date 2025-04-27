<?php
/** 联通支付
 *
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/3/25
 * Time: 3:46 PM
 */


class wap_payment_plugin_municomo2opay_server extends ectools_payment_app
{

    /**
     * 积分全额抵扣和部分现金支付都会回调该方法
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback( &$recv )
    {
        //存在php://input则为WAP端现金补差支付回调异步通知
        $pars = @file_get_contents( 'php://input' );
        $pars = json_decode( $pars, true );
        $bill_id = $pars['bill_id'];

        if ( $bill_id )
        {
            //todo 点滴pay支付完通知

            $ret['callback_source'] = 'server';

            \Neigou\Logger::General( 'municomo2o', array( 'action' => 'cash_init', 'data' => $recv, 'raw' => $pars ) );

            try
            {
                $sign = $pars['sign'];
                unset( $pars['sign'] );
                $check_sign = $this->sign_pay( $pars, true, true );

                if ( $check_sign !== $sign )
                {
                    throw new Exception( '支付回调签名错误' );
                }

                $pars['ext'] = json_decode( base64_decode( $pars['extend_data'] ), true );

                if ( $pars['ext']['status'] !== 'succ' )
                {
                    throw new Exception( '支付验证失败' );
                }

                //获取支付信息
                $orderModel = app::get( 'unicom' )->model( 'o2oPaymentOrder' );
                $orderRes = $orderModel->getOrderRaw( array( 'bill_id' => $bill_id ) );
                if ( !$orderRes )
                {
                    throw new Exception( '获取联通和内购订单信息失败' );
                }

                //用户信息
                $oo2oUserModel = app::get( 'unicom' )->model( 'o2ouser' );
                $userInfo = $oo2oUserModel->getUserInfoRawByMemberId( $orderRes['member_id'] );
                $extendInfo = json_decode( $userInfo['wap_extendInfo'], true );
                if ( empty( $userInfo ) || empty( $extendInfo ) )
                {
//                    throw new Exception( '没有用户或用户扩展信息' );
                }

                $config = app::get( 'ectools' )->getConf( 'ectools_payment_plugin_unicomo2opay' );
                $config = unserialize( $config );
                if ( !$config['setting'] )
                {
                    throw new Exception( '获取联通配置信息失败' );
                }

                //通知联通已支付完成
                $notify = array(
                    'method' => 'sendUserPayResult',
                    'data' => array(
                        "comCode" => $orderRes['comCode'],
                        "company" => $orderRes['company'],
                        "orderNo" => $orderRes['unicomOrderId'],
                        "payAmount" => number_format( $orderRes['orderPrice'] - $orderRes['pointCost'], 2 ),
                        "providerCode" => $config['setting']['systemId'],
                        "payStatus" => "0"
                    )
                );

                $errMsg = '';
                $result = kernel::single( 'unicom_request' )->requestOpenapi( $notify );
                if ( $result['Result'] != 'true' )
                {
                    \Neigou\Logger::General( 'municomo2o', array( 'action' => 'notify_unicom_fail', 'data' => $notify,'order'=>$orderRes,'result'=>$result ) );
                    throw new Exception( '支付通知联通失败' . $errMsg );
                }

                //bill 通知成功
                $billPost = array(
                    'bill_id' => $bill_id,
                    'set' => array( 'trade_no' => $pars['trade_no'] )
                );

                $pay_lib = kernel::single( "wap_payment_plugin_municomo2opay" );
                $pay_lib->notifyBill( $billPost );

                $ret['t_payed'] = $pars['t_payed']; //支付时间
                $ret['trade_no'] = $bill_id;//交易流水号为上报订单号,第三方唯一标识
                $ret['payment_id'] = $orderRes['payment_id']; //支付单号
                $ret['pay_app_id'] = 'municomo2opay';
                $ret['status'] = 'succ';

                $this->msg();

                \Neigou\Logger::General( 'municomo2o', array( 'action' => 'trade_succ', 'data' => $ret ,'req'=>$pars) );
            } catch ( Exception $e )
            {
                \Neigou\Logger::General( 'municomo2o', array( 'action' => 'trade_error', 'error_msg' => $e->getMessage(), 'data' => $recv ,'req'=>$pars) );
                $ret['status'] = 'invalid';
            }

            return $ret;
        }
        else
        {
            header( 'Content-Type:text/html; charset=utf-8' );
            \Neigou\Logger::General( 'municomo2o', array( 'remark' => 'recv_param', 'post_data' => $recv ) );

            $ret['callback_source'] = 'server';

            try
            {
                $config = app::get( 'ectools' )->getConf( 'ectools_payment_plugin_unicomo2opay' );
                $config = unserialize( $config );
                if ( !$config['setting'] )
                {
                    throw new Exception( '获取联通配置信息失败' );
                }

                //检查签名
                if ( $recv['payed'] )
                {
                    $currentSignStr = $config['setting']['systemId'] . $recv['timestamp'] . $recv['state'] . $recv['orderNo'] . $recv['pointCost'] . $recv['payed'];
                }
                else
                {
                    $currentSignStr = $config['setting']['systemId'] . $recv['timestamp'] . $recv['state'] . $recv['orderNo'] . $recv['pointCost'];
                }
                $newSing = strtoupper( md5( $currentSignStr ) );
                if ( $newSing != $recv['sign'] )
                {
                    throw new Exception( '签名验证失败' );
                }

                // 获取支付信息
                $orderModel = app::get( 'unicom' )->model( 'o2oPaymentOrder' );
                $orderRes = $orderModel->getOrderRaw( array( 'unicomOrderId' => $recv['orderNo'] ) );
                if ( !$orderRes )
                {
                    throw new Exception( '获取联通和内购订单信息失败' );
                }
                //现金支付
                if ( $recv['payed'] > 0 )
                {
                    //支付金额不正确
                    $pay_amount = bcadd( $orderRes['pointCost'], $recv['payed'], 2 );
                    if ( bccomp( $orderRes['orderPrice'], $pay_amount, 2 ) !== 0 )
                    {
                        throw new Exception( '现金支付金额错误' );
                    }
                }
                else if ( bccomp( $orderRes['orderPrice'], $recv['pointCost'], 2 ) === 1 )
                {
                    //Todo 联通部分积分支付完成后的回调->请求现金支付跳转页面

                    $result_msg = '订单错误';
                    $code = '';
                    $location_url = app::get( 'wap' )->router()->gen_url( array( 'app' => 'b2c', 'ctl' => 'wap_paycenter2', 'act' => 'result_failed', 'arg0' => $orderRes['platformOrderId'] ) );
                    $pay_url = ECSTORE_DOMAIN.'/'.$location_url;

                    //已支付
                    $order_info = kernel::single( "b2c_service_order" )->getOrderInfo( $orderRes['platformOrderId'] );
                    if ( $order_info['pay_status'] == 2 )
                    {
                        $result_msg = '订单已支付';
                        //0001-代表已成功支付（已现金支付但是通知联通失败防止用户二次支付）
                        $code = '0001';
                        $location_url = app::get( 'wap' )->router()->gen_url( array( 'app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'result_failed', 'arg0' => $orderRes['platformOrderId'] ) );
                        $pay_url = ECSTORE_DOMAIN . '/' . $location_url;
                    }

                    //现金补差
                    if ( $order_info['pay_status'] == 1 )
                    {
                        //wap现金支付链接
                        $code = '';
                        $pay_lib = kernel::single( "wap_payment_plugin_municomo2opay" );
                        $pay_url = $pay_lib->get_pay_url( $orderRes['platformOrderId'], $recv['orderNo'], bcsub( $orderRes['orderPrice'], $recv['pointCost'], 2 ) );
                        $result_msg = '请个人支付';
                        $orderModel->update( array( 'unicomOrderId' => $recv['orderNo'] ), array( 'pointCost' => $recv['pointCost'] ) );
                    }

                    $res_data = array(
                        'code' => $code,
                        'result' => $result_msg,
                        'resultCode' => $pay_url,
                        'resultMessage' => '',
                        'success' => true
                    );
                    echo json_encode( $res_data );
                    exit;
                }

                $ret['bank'] = app::get( 'ectools' )->_( '联通O2O支付' );
                $ret['currency'] = 'CNY';
                $ret['payment_id'] = $orderRes['payment_id']; //支付id
                $ret['paycost'] = '0.000'; //手续费
                $ret['account'] = $orderRes['createId']; //支付的账号, 支付人
                $ret['pay_account'] = $recv['providerCode']; //支付到哪个账号,
                $ret['money'] = $recv['orderPrice']; //订单金额
                $ret['cur_money'] = $recv['orderPrice']; //转换为当地币的金额
                $ret['trade_no'] = $recv['orderNo'];//交易流水号为上报订单号 第三方唯一标识
                $ret['t_payed'] = strtotime( $recv['timestamp'] ); //支付时间
                $ret['pay_app_id'] = "municomo2opay";
                $ret['pay_type'] = 'online';
                $ret['status'] = 'succ';
                echo $this->ret_result();
                \Neigou\Logger::General( 'municomo2o', array( 'action' => 'point_trade_succ', 'data' => $ret ) );
            } catch ( Exception $e )
            {
                \Neigou\Logger::General( 'municomo2o', array( 'action' => 'point_trade_error', 'error_msg' => $e->getMessage(), 'data' => $recv ) );
                echo 'fail';
                $ret['status'] = 'invalid';
            }

            return $ret;
        }
    }

    /**
     * 响应输出
     * @param $bool
     */
    private function msg()
    {
        echo "success";
    }

    private function sign_pay( $data, $sort = true, $encode = true )
    {
        $str = $this->_create_link_string( $data, $sort, $encode );
        $origin = 'unicom' . $str . 'unicom';
        return md5( $origin );
    }

    private function _create_link_string( $para, $sort = true, $encode = true )
    {
        if ( $para == NULL || !is_array( $para ) )
            return "";
        $linkString = "";
        if ( $sort )
        {
            $para = $this->argSort( $para );
        }
        while ( list ( $key, $value ) = each( $para ) )
        {
            if ( $encode )
            {
                $value = urlencode( $value );
            }
            $linkString .= $key . "=" . $value . "&";
        }

        // 去掉最后一个&字符
        $linkString = substr( $linkString, 0, strlen( $linkString ) - 2 );
        return $linkString;
    }

    /**
     * 数组排序
     * @param $para
     * @return mixed
     */
    private function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }


    /**
     * 支付成功回打支付成功信息给支付网关
     */
    public function ret_result( $paymentId )
    {
        $returnArr = array(
            'success' => true,
            'resultMessage' => '',
            'result' => "处理成功",
            'resultCode' => ''
        );
        return kernel::single( "wap_payment_plugin_municomo2opay" )->jsonEncodeTool( $returnArr );
    }
}