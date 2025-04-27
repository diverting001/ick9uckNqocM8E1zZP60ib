<?php
/** 联通支付
 *
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/3/25
 * Time: 3:46 PM
 */


class wap_payment_plugin_mtengweishipay_server extends ectools_payment_app
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
        \Neigou\Logger::General('notify.tengweishi.req', array('remark' => 'recv_param', 'post_data' => $recv));

        try{

            //参数检查
            $msg = '';
            $checkParamsRes = $this->_checkParams($recv,$msg);
            if (!$checkParamsRes){
                throw new Exception($msg);
            }

            //检查签名
            $this->params = $recv;
            unset($this->params['sign']);
            $sign =  kernel::single('wap_payment_plugin_mtengweishipay')->setSign($this->params);
            if ($sign !== $recv['sign']){
                throw new Exception('签名验证失败');
            }

            // 检查用户是否在腾威视支付成功
            if ($recv['status'] !== '0'){
                throw new Exception('支付失败, 腾威视返回: '.$recv['info']);
            }

            $ret['bank'] = app::get('ectools')->_('联腾威视支付');
            $ret['currency'] = 'CNY';
            $ret['payment_id'] = $recv['pay_id']; //支付id
            $ret['paycost'] = '0.000'; //手续费
            //$ret['account'] = $orderRes['createId']; //支付的账号, 支付人
            //$ret['pay_account'] = $recv['providerCode']; //支付到哪个账号,
            $ret['money'] = $recv['total_price']; //订单金额
            $ret['cur_money'] = $recv['total_price']; //转换为当地币的金额
            $ret['trade_no'] = $recv['pay_order_no'];//交易流水号为上报订单号 第三方唯一标识
            $ret['t_payed'] = strtotime($recv['pay_time']); //支付时间
            $ret['pay_app_id'] = "mtengweishipay";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            $ret['callback_source'] = 'server';

            \Neigou\Logger::General('ecstore.notify.tengweishipay', array('remark' => 'trade_succ', 'data' => $ret));
        }catch (Exception $e){
            \Neigou\Logger::General('ecstore.notify.tengweishipay',array('report_name' => 'ecstore.notify.tengweishipay.error','error_msg' => $e->getMessage(),'data' => $recv));
            $this->errMsg = $e->getMessage();
            $this->ret_result($recv['payment_id']);
        }
        return $ret;
    }

    /** 参数检查
     *
     * @param array $params
     * @param $msg
     * @return bool
     * @author liuming
     */
    private function _checkParams($params = array(),&$msg){
        $result = true;
        $mustKey = array(
            'pay_id','total_price','pay_order_no','pay_time','user_id'
        );

        $paramsKeys = array_keys($params);
        $diff = array_diff($mustKey,$paramsKeys);
        if ($diff){
            $msg = '参数:'.implode(',',$diff).'不能为空';
            $result = false;
        }

        return $result;
    }


    /**
     * 支付成功回打支付成功信息给支付网关
     */
    public function ret_result($paymentId){

        $findPayment = array();
        if(empty($this->errMsg)){
            $paymentsModel = app::get('ectools') -> model('payments');
            $findPayment = $paymentsModel->dump($paymentId, '*', '*');
        }

        $returnData = array(
            //'info' => '支付失败',
            'status' => -1,//0支付成功/-1支付失败
            'orderno' => $this->params['orderno'],
            'pay_order_no' => $this->params['pay_order_no'],
            'user_id' => $this->params['user_id'],
            'pay_time' => $this->params['pay_time'],
            'total_price' => $this->params['total_price'],
        );

        $returnData['info'] = $this->errMsg ? $this->errMsg : '支付失败';
        if ($findPayment && $findPayment['status'] == 'succ'){
            $returnData['info'] = '支付成功';
            $returnData['status'] = 0;//0支付成功/-1支付失败
        }


        $sign =  kernel::single('wap_payment_plugin_mtengweishipay')->setSign($returnData);
        $returnData['sing'] = $sign;

        echo json_encode($returnData);
    }
}