<?php

/**
 * 知心荟 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mhuaxingcps_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        \Neigou\Logger::General('ecstore.notify.mwanxin',array('remark'=>'notify_param','data'=>$recv));
        #键名与pay_setting中设置的一致
        if(kernel::single('wap_payment_plugin_mhuaxingcps')->is_return_vaild($recv)){
            $ret['payment_id'] = $recv['order_id'];
            $ret['account'] = $recv['mer_id'];
            $ret['bank'] = app::get('ectools')->_('华兴CPS支付');
            $ret['pay_account'] = '华兴cps';
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_fee'];
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_fee'];
            $ret['trade_no'] = $recv['trade_no'];//queryId 交易流水号 traceNo 系统跟踪号
            $ret['t_payed'] = time();
            $ret['pay_app_id'] = "mhuaxingcps";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            echo 'succ';
        }else{
            echo 'fail';
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mhuaxingcps',array('remark'=>'sign err','data'=>$recv));
        }
        return $ret;
    }
}