<?php

/**
 * 招行支付 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mcmbchina_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        \Neigou\Logger::General('ecstore.notify.mcmbchina', array('remark' => 'param_init', 'post_data'=>$GLOBALS["HTTP_RAW_POST_DATA"],'request_data'=>$_REQUEST));
        #键名与pay_setting中设置的一致
        if(empty($GLOBALS["HTTP_RAW_POST_DATA"])){
            $data = $recv;
        } else {
            parse_str($GLOBALS["HTTP_RAW_POST_DATA"],$data);
        }
        if($this->is_return_vaild($data)){
            $ret['payment_id'] = $data['mhtOrderNo'];
            $ret['bank'] = app::get('ectools')->_('招行微信支付');
            $ret['currency'] = 'CNY';
            $objMath = kernel::single('ectools_math');
            $ret['money'] = $objMath->number_multiple(array($data['mhtOrderAmt'], 0.01));
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $objMath->number_multiple(array($data['mhtOrderAmt'], 0.01));
            $ret['trade_no'] = $data['nowPayOrderNo'];
            $ret['t_payed'] =  strtotime($data['payTime']);
            $ret['pay_app_id'] = "mcmbchina";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            $this->msg(true);
        }else{
            $this->msg(false);
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mcmbchina',array('remark'=>'sign err','data'=>$GLOBALS["HTTP_RAW_POST_DATA"],'dataA'=>$data));
        }
        return $ret;
    }

    /**
     * 响应输出
     * @param $bool
     */
    private function msg($bool){
        if($bool){
            echo 'success=Y';//只有1代表成功
        } else {
            echo 'success=N';//只有1代表成功
        }
    }

    public function _create_link_string($para, $sort, $encode){
        if($para == NULL || !is_array($para))
            return "";
        $linkString = "";
        if($sort){
            ksort ( $para );
            reset ( $para );
        }
        foreach ($para as $key=>$value) {
            if($encode) {
                $value = urlencode($value);
            }
            $linkString .=$key.'='.$value.'&';
        }
        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
        return $linkString;
    }

    public function sign($params) {
        $str = $this->_create_link_string($params,true,false);
        $str.='&'.md5($this->getConf('sign_key','wap_payment_plugin_mcmbchina'));
        return md5($str);
    }

    /**
     * 检验返回数据合法性
     * @param $param string
     * @param $data array
     * @access private
     * @return boolean
     */
    public function is_return_vaild($data) {
        //对字符串进行处理
        $sign = $data['signature'];
        unset($data['signature']);
        $calc_sign = $this->sign($data);
        if($sign==$calc_sign){
            return true;
        } else {
            \Neigou\Logger::General('ecstore.notify.mcmbchina.sign_err',array('remark'=>'sign_err','data'=>$param));
            return false;
        }
    }
}