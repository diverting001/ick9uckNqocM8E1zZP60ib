<?php
/*
 * baiofupay server callback
 *
*/

class wap_payment_plugin_mbaifupay_server extends ectools_payment_app {

    public function callback(&$recv){
        if($this -> is_return_vaild($recv)){
            $ret['payment_id'] = $recv['order_no'];
            $ret['account'] = $this -> id;
            $ret['bank'] = app::get('wap')->_('手机百付宝');
            $ret['pay_account'] = app::get('wap')->_('付款帐号');
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_amount'] / 100; 
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_amount'] / 100; 
            $ret['trade_no'] = $recv['bfb_order_no'];
            $ret['t_payed'] = (strtotime($recv['pay_time']) ? strtotime($recv['pay_time']) : time());
            $ret['pay_app_id'] = "mbaifupay";
            $ret['pay_type'] = 'online';
            $ret['memo'] = ''; 
            if($recv['pay_result'] == 1){
                echo '<meta name="VIP_BFB_PAYMENT" content="BAIFUBAO">';
                $ret['status'] = 'succ';
            }else{
                echo "fail";
                $ret['status'] = 'failed';
            }
        }else{
            $ret['message'] = 'Invalid Sign';
            $ret['status'] = 'invalid'; 
        }
        $ret['callback_source'] = 'server';
        return $ret;
    }

    public function is_return_vaild($request){
        $this -> id = $this->getConf('mer_id', 'wap_payment_plugin_mbaifupay');
        $this -> key = $this->getConf('mer_key', 'wap_payment_plugin_mbaifupay'); 

        $sign_temp = $request['sign'];
        unset($request['sign']);
        $sign = $this -> get_sign($request);
        if($sign == $sign_temp){
            return true;
        }else{
            return false;
        }
    }

    public function get_sign($arr){
        $arr = $this -> sort_array($arr);
        $arr['key'] = $this -> key;
        // debug($arr);
        $str = $this -> build_str($arr);
        return md5($str);
    }

    public function sort_array($arr){
        ksort($arr);
        return $arr;
    }

    public function build_str($arr){
        $arr_temp = array ();
        foreach ($arr as $key => $val) {
            $arr_temp [] = $key . '=' . $val;
        }
        $sign_str = implode('&', $arr_temp);
        return $sign_str;
    }

}