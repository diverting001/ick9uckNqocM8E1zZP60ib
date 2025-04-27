<?php
/*
 * mallinpay server callback
 *
*/

class wap_payment_plugin_mallinpayjifen_server extends ectools_payment_app {

    public function callback(&$recv){
        $this -> id = $this->getConf('mer_id', 'wap_payment_plugin_mallinpay');
        $ret['callback_source'] = 'server';
        \Neigou\Logger::General("notify.tonglianjifen.recv", array("data"=>$recv));
        $sign_str = $recv['sysid'] . $recv['rps'] . $recv['timestamp'];
        $sign = $recv['sign'];
        $sign = urldecode($sign);
        $sign = str_replace(' ','+',$sign);
        if($this -> verify($sign_str,$sign)){
            $recv['rps'] = json_decode($recv['rps'],true);
            $ret['payment_id'] = $recv['rps']['returnValue']['orderNo'];
            $ret['account'] = $this -> id;
            $ret['bank'] = app::get('wap')->_('通联支付');
            $ret['pay_account'] = app::get('wap')->_('付款帐号');
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['rps']['returnValue']['orderMoney'] / 100; 
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['rps']['returnValue']['orderMoney'] / 100; 
            $ret['trade_no'] = $recv['rps']['returnValue']['payOrderNo'];
            $ret['t_payed'] = (strtotime($recv['rps']['returnValue']['payDatetime']) ? strtotime($recv['rps']['returnValue']['payDatetime']) : time());
            $ret['pay_app_id'] = "mallinpay";
            $ret['pay_type'] = 'online';
            $ret['memo'] = ''; 
            if($recv['rps']['status'] == 'OK'){
                \Neigou\Logger::General("notify.tonglianjifen.succ", array('remark'=>'succ',"data"=>$recv['rps'],'ret'=>$ret));
                ob_clean();
                header('HTTP/1.1 200 OK');
                $ret['status'] = 'succ';
            }else{
                \Neigou\Logger::General("notify.tonglianjifen.fail", array('remark'=>'pay fail',"data"=>$recv['rps'],'ret'=>$ret));
                ob_clean();
                header("HTTP/1.1 400 Bad Request");
                $ret['status'] = 'failed';
            }
        }else{
            \Neigou\Logger::General("notify.tonglianjifen.fail", array('remark'=>'sign err',"data"=>$recv,'sign_str'=>$sign_str,'sign'=>$sign));
            ob_clean();
            header("HTTP/1.1 400 Bad Request");
            $ret['message'] = 'Invalid Sign';
            $ret['status'] = 'invalid'; 
        }
        return $ret;
    }
    //验证签名
    private function verify($data, $signature,$jf_verify=false) {
        $signature = base64_decode($signature);
        $certs = array();
        //积分支付验证
        openssl_pkcs12_read(file_get_contents(TONGLIAN_JF_CERT_PATH . TONGLIAN_JF_PFX), $certs,  TONGLIAN_JF_PASSWORD);
        if(!$certs) return false;
        $result = (bool) openssl_verify($data, $signature, $certs['cert']); //openssl_verify验签成功返回1，失败0，错误返回-1
        return $result;
    }
}