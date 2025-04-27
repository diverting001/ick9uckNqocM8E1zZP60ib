<?php

/**
 * 我买网企业支付 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mqalipaywomai_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        \Neigou\Logger::General('ecstore.notify.mqalipaywomai',array('remark'=>'notify_param','data'=>$recv,'raw_data'=>$GLOBALS["HTTP_RAW_POST_DATA"],'request_data'=>$_REQUEST));
        #键名与pay_setting中设置的一致
        $recv = json_decode($recv['data'],true);
        if($this->is_return_vaild($recv)){
            $payment_id = str_replace('QYDD','',$recv['outTradeNo']);
            $ret['payment_id'] = $payment_id;
            $ret['account'] = $recv['bankTradeNo'];
            $ret['bank'] = app::get('ectools')->_('我买网支付');
            $ret['pay_account'] = $recv['openid'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['totalAmount'];
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['totalAmount'];
            $ret['trade_no'] = $recv['tradeNo'];//notify_id
            $ret['t_payed'] = time();
            $ret['pay_app_id'] = 'mqalipaywomai';
            $ret['pay_type'] = 'online';
            if($recv['tradeStatus']=='TRADE_SUCCESS') {//为TRADE_SUCCESS 代表支付结果成功
                $this->msg(true);
                $ret['status'] = 'succ';
            }else{
                $this->msg(false);
                $ret['status'] = 'failed';
                \Neigou\Logger::General('ecstore.notify.mqalipaywomai',array('remark'=>'pay rzt err','data'=>$recv));
            }
        }else{
            $this->msg(false);
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mqalipaywomai',array('remark'=>'sign err','data'=>$recv));
        }
        return $ret;
    }


    /**
     * 响应输出
     * @param $bool
     */
    private function msg($bool){
        if($bool){
            $data['errcode'] = 'success';
            $data['errmsg'] = 'ok';
        } else {
            $data['errcode'] = 'fail';
            $data['errmsg'] = 'fail';
        }
        echo $data['errcode'];
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
        $req_sign = $this->genSign($param);
        if ($sign==$req_sign) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 计算签名
     * @param $data
     * @return string
     */
    public function genSign($data){
        $linkStr = $this->_create_link_string($data,true,false);
        $linkStr .= 'key='.$this->getConf('md5_key','wap_payment_plugin_mqalipaywomai');
        \Neigou\Logger::General('ecstore.mqalipaywomai.notify.linkStr',array('action'=>'make sign','linkStr'=>$linkStr,'sign'=>md5($linkStr)));
        return strtoupper(md5($linkStr));
    }

    /**
     * 组合字符串
     * @param $para
     * @param $sort
     * @param $encode
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
            $linkString .= $key . "=" . $value.'&';
        }
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