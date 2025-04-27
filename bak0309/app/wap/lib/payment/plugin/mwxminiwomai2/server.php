<?php

/**
 * 我买网 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mwxminiwomai2_server extends ectools_payment_app {

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
        \Neigou\Logger::General('ecstore.notify.mwxminiwomai2',array('remark'=>'notify_param','data'=>$recv,'raw_data'=>$GLOBALS["HTTP_RAW_POST_DATA"],'request_data'=>$_REQUEST));
        #键名与pay_setting中设置的一致
        if($this->is_return_vaild($recv)){
            $payment_id = str_replace($this->getConf('order_prefix','wap_payment_plugin_mwxminiwomai2'),'',$recv['outTradeNo']);
            $ret['payment_id'] = $payment_id;
            $ret['bank'] = app::get('ectools')->_('我买网微信小程序支付');
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['totalAmount']/100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['totalAmount']/100;
            $ret['trade_no'] = $recv['tradeNo'];//notify_id
            $ret['t_payed'] = time();
            $ret['pay_app_id'] = 'mwxminiwomai2';
            $ret['pay_type'] = 'online';
            if($recv['tradeStatus']=='TRADE_SUCCESS') {//为0 代表支付结果成功
                $this->msg(true);
                $ret['status'] = 'succ';
            }else{
                $this->msg(false);
                $ret['status'] = 'invalid';
                \Neigou\Logger::General('ecstore.notify.mwxminiwomai2',array('remark'=>'result_err','data'=>$recv));
            }
        }else{
            $this->msg(false);
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mwxminiwomai2',array('remark'=>'sign_err','data'=>$recv));
        }
        return $ret;
    }

    public function redirect(){
        $code = trim($_REQUEST['code']);
        if(empty($code)){
            exit('Access Deny ! Please Check Code');
        }
        $payment_id = trim($_REQUEST['payment_id']);
        $_redis = kernel::single('base_sharedkvstore');
        //获取支付方式信息
        $obj_payments = app::get('ectools')->model('order_bills');
        $sdf = $obj_payments->getRow('rel_id,money',array('bill_id'=>$payment_id));

        $data['order_id'] = $sdf['rel_id'];
        $data['combination_pay'] = false;
        //判断当前支付单号是否由微信小程序支付方式产生
        $fields = array();
        $_redis -> fetch('store:mwxminiwomai2:payment_info',$payment_id,$fields);
        if(!empty($fields)){
            $data['def_pay']['pay_app_id'] = 'mwxminiwomai2';
        } else {
            $data['def_pay']['pay_app_id'] = 'mwxqwomai';
        }
        $data['def_pay']['cur_money'] = $sdf['money'];
        $query['payment'] = $data;
        $query['code'] = $code;
        //设置订单对应的payment_id 为当前
        $_redis->store('store:mwxminiwomai2:payment',$sdf['rel_id'],$payment_id,300);
        $_redis->store('store:mwxminiwomai2:code',$payment_id,$code,300);
        $r =  $url = app::get('wap')->router()->gen_url(array('app'=>'b2c','ctl'=>'wap_paycenter2','act'=>'dopayment','arg0'=>'pop_order'));
        header('Location: '.$r.'?'.http_build_query($query));die;
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
        $req_sign = kernel::single('wap_payment_plugin_mwxminiwomai2')->genSign($param);
        if ($sign==$req_sign) {
            return true;
        } else {
            return false;
        }
    }
}