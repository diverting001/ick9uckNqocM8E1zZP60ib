<?php

/**
 * 我买网 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mwxminiwomai_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Content-Type:text/html; charset=utf-8');
        $json = $recv["data"];
        $recv = json_decode($json,true);
        $ret['callback_source'] = 'server';
        \Neigou\Logger::General('ecstore.notify.mwxminiwomai',array('remark'=>'notify_param','data'=>$recv,'raw_data'=>$GLOBALS["HTTP_RAW_POST_DATA"],'request_data'=>$_REQUEST));
        #键名与pay_setting中设置的一致
        if($this->is_return_vaild($recv)){
            $payment_id = str_replace($this->getConf('order_prefix','wap_payment_plugin_mwxminiwomai'),'',$recv['outTradeNo']);
            $ret['payment_id'] = $payment_id;
            $ret['bank'] = app::get('ectools')->_('我买网微信小程序支付');
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['totalAmount']/100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['totalAmount']/100;
            $ret['trade_no'] = $recv['tradeNo'];//notify_id
            $ret['t_payed'] = time();
            $ret['pay_app_id'] = 'mwxminiwomai';
            $ret['pay_type'] = 'online';
            if($recv['tradeStatus']=='TRADE_SUCCESS') {//为0 代表支付结果成功
                $this->msg(true);
                $ret['status'] = 'succ';
            }else{
                $this->msg(false);
                $ret['status'] = 'invalid';
                \Neigou\Logger::General('ecstore.notify.mwxminiwomai',array('remark'=>'pay rzt err','data'=>$recv));
            }
        }else{
            $this->msg(false);
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mwxminiwomai',array('remark'=>'sign err','data'=>$recv));
        }
        return $ret;
    }

    public function query(){
        $payment_id = trim($_POST['out_trade_no']);
        $payment_id = str_replace($this->getConf('order_prefix','wap_payment_plugin_mwxminiwomai'),'',$payment_id);
        $obj_payments = app::get('ectools')->model('payments');
        $sdf = $obj_payments->dump($payment_id, '*', '*');
        if($this->is_return_vaild($_POST)){
            if ($sdf){
                $out['errcode'] = 'SUCCESS';
                $out['errmsg'] = '成功';
                $out['body'] = $this->getConf('order_prefix','wap_payment_plugin_mwxminiwomai').'-'.$sdf['payment_id'];
                $out['attach'] = $sdf['payment_id'];
                $out['out_trade_no'] = $this->getConf('order_prefix','wap_payment_plugin_mwxminiwomai').$sdf['payment_id'];
                $out['userID'] = $sdf['member_id'];
                $out['total_fee'] = number_format($sdf['cur_money'],2,".","")*100;
                $out['time_start'] = $sdf['t_begin'];
                $out['time_expire'] = $sdf['t_begin']+2380;
                $out['sign'] = kernel::single('wap_payment_plugin_mwxminiwomai')->genSign($out);
            } else {
                $out['errcode'] = 'FAIL';
                $out['errmsg'] = '支付单查询失败';
            }
        } else {
            $out['errcode'] = 'FAIL';
            $out['errmsg'] = 'Sign Err';
        }
        \Neigou\Logger::General('ecstore.notify.mwxminiwomai.queryOrder',array('remark'=>'query_order','out'=>$out,'raw_data'=>$GLOBALS["HTTP_RAW_POST_DATA"],'request_data'=>$_REQUEST));
        echo json_encode($out);
    }

    /**
     * 响应输出
     * @param $bool
     */
    private function msg($bool){
        if($bool){
            $msg = 'success';
        } else {
            $msg = 'fail';
        }
        echo $msg;
    }

    /**
     * 检验返回数据合法性
     * @param $param array
     * @access private
     * @return boolean
     */
    public function is_return_vaild($param) {
        $sign = $param['sign'];
        unset($param['sign']);
        $req_sign = kernel::single('wap_payment_plugin_mwxminiwomai')->genSign($param);
        if ($sign==$req_sign) {
            return true;
        } else {
            return false;
        }
    }
}