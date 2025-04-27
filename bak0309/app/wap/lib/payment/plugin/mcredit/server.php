<?php

/**
 * 信用支付 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mcredit_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        \Neigou\Logger::General('ecstore.notify.mcredit',array('remark'=>'notify_param','data'=>$recv));
        #键名与pay_setting中设置的一致
        if($this->is_return_vaild($recv)){
            $ret['payment_id'] = $recv['trade_no'];
            $ret['bank'] = app::get('ectools')->_('信用支付');
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['cur_money'];
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['cur_money'];
            $ret['trade_no'] = $recv['credit_id'];
            $ret['t_payed'] =  time();
            $ret['pay_app_id'] = "mcredit";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            $this->msg(true);
        }else{
            $this->msg(false);
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mcredit',array('remark'=>'sign err','data'=>$recv));
        }
        return $ret;
    }

    /**
     * 响应输出
     * @param $bool
     */
    private function msg($bool){
        if($bool){
            echo 1;//只有1代表成功
        } else {
            echo 2;
        }
    }

    /**
     * 检验返回数据合法性
     * @param $data array
     * @access private
     * @return boolean
     */
    public function is_return_vaild($data) {
        $sign = $data['sign'];
        unset($data['sign']);
        if($sign==md5($data['trade_no'].$this->getConf('sign_key','wap_payment_plugin_mcredit').$data['cur_money'].$data['credit_id'])){
            return true;
        } else {
            \Neigou\Logger::General('ecstore.notify.mcredit.sign_err',array('remark'=>'sign_err','data'=>$data));
            return false;
        }
    }
}