<?php

class wap_payment_plugin_mbeifupay_server extends ectools_payment_app {

    public $_msg = array(
            0 => '成功',
            1 => '失败',
            105 => '签名验证失败',
            117 => '请求方法错误',
            118 => '没有请求参数',
            119 => '交易状态错误',
            120 => '关键参数错误',
        );

    public function callback(&$recv){
        $this -> id = $this->getConf('mer_id', 'wap_payment_plugin_mbeifupay');
        $this -> key = $this->getConf('mer_key', 'wap_payment_plugin_mbeifupay'); 
        $ret = array();
        $ret['callback_source'] = 'server';

        if($recv['trade_status'] != 0 && $recv['trade_status'] != 1){
            echo $this -> stdout(119);
            $ret['status'] = 'failed';
            return $ret;
        }
        if(!$recv['ordercode']){
            echo $this -> stdout(120,' [ordercode]');
            $ret['status'] = 'failed';
            return $ret;
        }
        if(!$recv['payserial']){
            echo $this -> stdout(120,' [payserial]');
            $ret['status'] = 'failed';
            return $ret;
        }
        if(!isset($recv['totalmoney'])){
            echo $this -> stdout(120,' [totalmoney]');
            $ret['status'] = 'failed';
            return $ret;
        }

        if(!$this -> ckSign($recv)){
            echo $this -> stdout(105);
            $ret['status'] = 'invalid'; 
            return $ret;
        }else{
            $ret['payment_id'] = $recv['payserial'];
            $ret['account'] = $this -> id;
            $ret['bank'] = app::get('wap')->_('北付宝');
            $ret['pay_account'] = app::get('wap')->_('付款帐号');
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['totalmoney'] / 100; 
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['totalmoney'] / 100; 
            $ret['trade_no'] = $recv['trade_no'];
            $ret['t_payed'] = $recv['notify_time'] ? $recv['notify_time'] : time();
            $ret['pay_app_id'] = "mbeifupay";
            $ret['pay_type'] = 'online';
            $ret['memo'] = ''; 
            $ret['status'] = 'succ';

            $payment_data = array();
            $payment_data['payment'] = $recv['real_paytype'];
            $payment_data['payment_id'] = $recv['payserial'];
            $payment_data['payment_type'] = 'mbeifupay';
            $_payment = app::get('b2c') -> model('order_payment');
            $_payment -> save($payment_data);

            echo $this -> stdout(0);
            return $ret;
        }
    }

    public function mkSign($params = array()){
        ksort($params);
        // $params['appPass'] = $this -> key;
        $str = $this -> bulidReq($params);
        $sign = md5($str . $this -> key);
        return $sign;
    }

    public function bulidReq($params){
        $temp = array();
        foreach ($params as $k => $v) {
            $temp[] = "{$k}={$v}";
        }
        $str = implode('&',$temp);  
        return $str;
    }

    public function ckSign($params = array()){
        if(!$params['sign']) return false;
        $sign = $params['sign'];
        unset($params['sign']);
        $sign_str = $this -> mkSign($params);
        if($sign === $sign_str)
            return true;
        return false;
    }

    public function stdout($code = 0,$msg = ''){
        $code = $code ? $code : 0;
        $arr = array(
                're_code' => $code,
                're_info' => $this -> _msg[$code] . $msg
            );
        return json_encode($arr);
    }

}