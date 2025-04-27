<?php
/*
 * mallinpay server callback
 *
*/

class wap_payment_plugin_msdallinpay_server extends ectools_payment_app {

    public function callback(&$recv){
        $this -> id = $this->getConf('mer_id', 'wap_payment_plugin_msdallinpay');
        $this -> jf_id = $this->getConf('mer_id_jf', 'wap_payment_plugin_msdallinpay');//通联积分商户号

        $ret['callback_source'] = 'server';

        \Neigou\Logger::General("ecstore.notify.msdallinpay", array("remark"=>'param_init',"data"=>json_encode($recv)));

        $sign_str = $recv['sysid'] . $recv['rps'] . $recv['timestamp'];
        $jf_verify = false;
        //判断是否是积分支付商户号
        if($recv['sysid']==$this->jf_id){
            $this->id = $this->jf_id;
            $jf_verify = true;
        }
        $sign = $recv['sign'];
        $sign = urldecode($sign);
        $sign = str_replace(' ','+',$sign);

        if($this -> verify($sign_str,$sign,$jf_verify)){
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
            $ret['pay_app_id'] = "msdallinpay";
            $ret['pay_type'] = 'online';
            $ret['memo'] = ''; 
            if($recv['rps']['status'] == 'OK'){
                ob_clean();
                header('HTTP/1.1 200 OK');
                $ret['status'] = 'succ';
            }else{
                ob_clean();
                header("HTTP/1.1 400 Bad Request");
                $ret['status'] = 'failed';
            }
        }else{
            \Neigou\Logger::General("ecstore.notify.msdallinpay", array("remark"=>'sign_err',"data"=>json_encode($recv)));
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
        if($jf_verify==true){
            openssl_pkcs12_read(file_get_contents(TONGLIAN_JF_CERT_PATH . TONGLIAN_JF_PFX), $certs,  TONGLIAN_JF_PASSWORD);
        } else {
            openssl_pkcs12_read(file_get_contents(TONGLIAN_CERT_PATH . TONGLIAN_PFX), $certs,  TONGLIAN_PASSWORD);
        }

        if(!$certs) return false;
        $result = (bool) openssl_verify($data, $signature, $certs['cert']); //openssl_verify验签成功返回1，失败0，错误返回-1
        return $result;
    }
}