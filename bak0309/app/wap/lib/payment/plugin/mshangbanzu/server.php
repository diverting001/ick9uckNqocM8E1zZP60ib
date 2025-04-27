<?php

/**
 * 上班族支付 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mshangbanzu_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        \Neigou\Logger::General('ecstore.ectools.mshangbanzu',array('remark'=>'notify_call','data'=>$recv));
        #键名与pay_setting中设置的一致
        $mer_id = $this->getConf('appid', 'wap_payment_plugin_mshangbanzu');
        if($this->is_return_vaild($recv)){
            $ret['payment_id'] = $recv['order_id'];
            $ret['account'] = $mer_id;
            $ret['bank'] = app::get('ectools')->_('WAP上班族支付');
            $ret['pay_account'] = 'wechat';
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['price']/100;//TODO 确认是否是这个字段 清算金额
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['price']/100;
            $ret['trade_no'] = $recv['transaction_id'];//queryId 交易流水号 traceNo 系统跟踪号
            $ret['t_payed'] = strtotime($recv['deal_time']) ? strtotime($recv['deal_time']) : time();
            $ret['pay_app_id'] = "mshangbanzu";
            $ret['pay_type'] = 'online';
            if($recv['result']=='SUCCESS') {
                echo 'true';
                $ret['status'] = 'succ';
            }else{
                echo 'false';
                $ret['status'] = 'failed';
                \Neigou\Logger::General('ecstore.ectools.mshangbanzu',array('remark'=>'pay rzt err','data'=>$recv));
            }
        }else{
            echo 'false';
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.ectools.mshangbanzu',array('remark'=>'sign err','data'=>$recv));
        }
        return $ret;
    }

    /**
     * 检验返回数据合法性
     * @param $params array
     * @access private
     * @return boolean
     */
    public function is_return_vaild($params) {
        $app_id = $this->getConf('appid', 'wap_payment_plugin_mshangbanzu');
        $secret = $this->getConf('secret', 'wap_payment_plugin_mshangbanzu');
        $order_id = $params['order_id'];
        $sign = $params['sign'];
        $make_sign = md5($app_id.$order_id.$secret);
        if($sign==$make_sign){
            return true;
        } else {
            return false;
        }
    }
}