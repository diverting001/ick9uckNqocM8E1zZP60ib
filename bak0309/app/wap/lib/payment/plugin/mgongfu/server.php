<?php
/** 第三方支付成功通知类
 *
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/3/25
 * Time: 3:46 PM
 */


class wap_payment_plugin_mgongfu_server extends ectools_payment_app
{
    private $params = array();
    private $errMsg;

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recvData)
    {
        header('Content-Type:text/html; charset=utf-8');
        \Neigou\Logger::Debug('notify.mgongfu.req', array('remark' => 'recv_param', 'post_data' => $recv,'method' => __METHOD__));

        try{
            //检查签名
            $this->params = $recvData;
            $recv = json_decode($this->params['data'],true);
            unset($this->params['sign']);
            $commonData = array();
            if ($recvData['timestamp']){
                $commonData['timestamp'] = $recvData['timestamp'];
            }
            $sign =  kernel::single('wap_payment_plugin_mgongfu')->setSign($commonData,$this->params['data']);
            if ($sign != $recvData['sign']){
                throw new Exception('签名验证失败');
            }

            $ret['bank'] = app::get('ectools')->_('工福支付');
            $ret['currency'] = 'CNY';
            $ret['payment_id'] = $recv['thirdPayId']; //支付id
            $ret['paycost'] = '0.000'; //手续费
            //$ret['account'] = ''; //支付的账号, 支付人
            //$ret['pay_account'] = $recv['providerCode']; //支付到哪个账号,
            $ret['money'] = $recv['payTotal']; //订单金额
            $ret['cur_money'] = $recv['payTotal'] ; //转换为当地币的金额
            $ret['trade_no'] = $recv['payId'];//交易流水号为上报订单号 第三方唯一标识
            $ret['t_payed'] = $recv['notify_time'] ? $recv['notify_time'] : time(); //支付时间
            $ret['pay_app_id'] = "mgongfu";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            $ret['callback_source'] = 'server';
            $this->prt_result($recv['thirdPayId']);
            \Neigou\Logger::General('ecstore.notify.mgongfu', array('remark' => 'trade_succ', 'data' => $ret,'recv' => $recv));
        }catch (Exception $e){
            \Neigou\Logger::General('ecstore.notify.mgongfu',array('report_name' => 'ecstore.notify.mgongfu.error','error_msg' => $e->getMessage(),'data' => $recv,'recv_data' => $recvData,'dump_data' => var_dump($this->params['data'])));
            $this->errMsg = $e->getMessage();
            $ret['status'] = 'invalid';
            $this->prt_result($recv['thirdPayId']);
        }
        return $ret;
    }


    public function ret_result($paymentId)
    {
    }
    /**
     * 支付成功回打支付成功信息给支付网关
     */
    private function prt_result($paymentId){
        $findPayment = array();
        if(empty($this->errMsg)){
            $paymentsModel = app::get('ectools') -> model('payments');
            $findPayment = $paymentsModel->dump($paymentId, '*', '*');
        }

        $returnData = array(
            //'info' => '支付失败',
            'code' => 0,//1支付成功/0支付失败
            'msg' => '支付失败'
        );

        $returnData['msg'] = $this->errMsg ? $this->errMsg : '支付失败';
        if ($findPayment && $findPayment['status'] == 'succ'){
            $returnData['msg'] = '支付成功';
            $returnData['code'] = 1;//1支付成功
        }


        //$sign =  kernel::single('wap_payment_plugin_mtengweishipay')->setSign(array());
        //$returnData['sing'] = $sign;
        echo json_encode($returnData);
    }
}