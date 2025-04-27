<?php

/**
 * 福优支付 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mfuyou_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        $mer_id = $this->getConf('mer_id', 'wap_payment_plugin_mfuyou');
        if($this->is_return_vaild($recv)){
            if($recv['trade_status']==1){
                $ret['payment_id'] = $recv['orderNo'];
                $ret['account'] = $mer_id;
                $ret['bank'] = app::get('ectools')->_('福优支付');
                $ret['pay_account'] = $recv['customNo'];
                $ret['currency'] = 'CNY';
                $ret['money'] = $recv['settleAmt'];
                $ret['paycost'] = '0.000';
                $ret['cur_money'] = $recv['settleAmt'];
                $ret['trade_no'] = $recv['orderNoFlx'];//queryId 交易流水号 traceNo 系统跟踪号
                $ret['t_payed'] = $recv['notify_time'];
                $ret['pay_app_id'] = "mfuyou";
                $ret['pay_type'] = 'online';
                $ret['status'] = 'succ';
                echo 'succ';
            } else {
                $ret['status']='invalid';
                echo 'fail';
                \Neigou\Logger::General('ecstore.notify.mfuyou', array('remark' => 'trade_status_err', 'data' => $recv));
            }

        }else{
            echo 'fail';
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mfuyou',array('remark'=>'sign_err','data'=>$recv));
        }
        return $ret;

    }

    /**
     * 检验返回数据合法性
     * @param $params
     * @access private
     * @return boolean
     */
    public function is_return_vaild($params) {
        $signature_str = $params ['sign'];
        unset ( $params ['sign'] );
        $str = $this->_create_link_string($params,true,false);
        $str = $str.$this->getConf('client_secret', 'wap_payment_plugin_mfuyou');;
        $sign = md5($str);
        if ($sign==$signature_str) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 将数组转换成String
     * @param $para array 参数
     * @param $sort bool 是否排序
     * @param $encode string 是否urlencode
     * @return string
     */
    public function _create_link_string($para, $sort, $encode){
        if($para == NULL || !is_array($para))
            return "";
        $linkString = "";
        if($sort){
            $para = $this->argSort ( $para );
        }
        while ( list ( $key, $value ) = each( $para ) ) {
            if ($encode) {
                $value = urlencode ( $value );
            }
            $linkString .= $key . "=" . $value . "&";
        }

        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
        return $linkString;
    }

    /**
     * 数组排序
     * @param $para
     * @return mixed
     */
    function argSort($para) {
        ksort ( $para );
        reset ( $para );
        return $para;
    }
}