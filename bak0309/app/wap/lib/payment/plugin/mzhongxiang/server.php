<?php
/** 第三方支付成功通知类
 *
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/3/25
 * Time: 3:46 PM
 */


class wap_payment_plugin_mzhongxiang_server extends ectools_payment_app
{
    private $params = array();
    private $errMsg;

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv)
    {
        header('Content-Type:text/html; charset=utf-8');
        \Neigou\Logger::Debug('notify.mzhongxiang.req', array('remark' => 'recv_param', 'post_data' => $recv,'method' => __METHOD__));

        try{

            //检查签名
            $this->params = $recv;
            unset($this->params['sign']);
            $sign =  kernel::single('wap_payment_plugin_mzhongxiang')->setSign($this->params);
            if ($sign != $recv['sign']){
                throw new Exception('签名验证失败');
            }

            $ret['bank'] = app::get('ectools')->_('众享支付');
            $ret['currency'] = 'CNY';
            $ret['payment_id'] = $recv['out_trade_no']; //支付id
            $ret['paycost'] = '0.000'; //手续费
            $ret['account'] = $recv['account']; //支付的账号, 支付人
            //$ret['pay_account'] = $recv['providerCode']; //支付到哪个账号,
            $ret['money'] = $recv['total_fee'] / 100; //订单金额
            $ret['cur_money'] = $recv['total_fee'] / 100; //转换为当地币的金额
            $ret['trade_no'] = $recv['trade_no'];//交易流水号为上报订单号 第三方唯一标识
            $ret['t_payed'] = $recv['notify_time'] ? $recv['notify_time'] : time(); //支付时间
            $ret['pay_app_id'] = "mzhongxiang";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            $ret['callback_source'] = 'server';
            \Neigou\Logger::General('ecstore.notify.mzhongxiang', array('remark' => 'trade_succ', 'data' => $ret,'recv' => $recv));
            echo 'succ';
        }catch (Exception $e){
            \Neigou\Logger::General('ecstore.notify.mzhongxiang',array('report_name' => 'ecstore.notify.mzhongxiang.error','error_msg' => $e->getMessage(),'data' => $recv,'recv' => $recv));
            $this->errMsg = $e->getMessage();
            $ret['status'] = 'invalid';
            echo 'failed';
        }
        return $ret;
    }



    /**
     * 支付成功回打支付成功信息给支付网关
     */
    public function ret_result($paymentId){

//        $findPayment = array();
//        if(empty($this->errMsg)){
//            $paymentsModel = app::get('ectools') -> model('payments');
//            $findPayment = $paymentsModel->dump($paymentId, '*', '*');
//        }
//
//        $returnData = array(
//            //'info' => '支付失败',
//            'status' => -1,//0支付成功/-1支付失败
//            'orderno' => $this->params['orderno'],
//            'pay_order_no' => $this->params['pay_order_no'],
//            'user_id' => $this->params['user_id'],
//            'pay_time' => $this->params['pay_time'],
//            'total_price' => $this->params['total_price'],
//        );
//
//        $returnData['info'] = $this->errMsg ? $this->errMsg : '支付失败';
//        if ($findPayment && $findPayment['status'] == 'succ'){
//            $returnData['info'] = '支付成功';
//            $returnData['status'] = 0;//0支付成功/-1支付失败
//        }
//
//
//        $sign =  kernel::single('wap_payment_plugin_mtengweishipay')->setSign($returnData);
//        $returnData['sing'] = $sign;
//
//        echo json_encode($returnData);
    }
}