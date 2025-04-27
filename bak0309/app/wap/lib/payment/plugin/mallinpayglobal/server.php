<?php
/*
 * mallinpayglobal server callback
 *
*/

class wap_payment_plugin_mallinpayglobal_server extends ectools_payment_app {

    public function callback(&$recv){
        $this -> id = $this->getConf('mer_id', 'wap_payment_plugin_mallinpayglobal');

        $ret['callback_source'] = 'server';

        \Neigou\Logger::General("tonglian.tonglian.server", array("data"=>json_encode($recv)));

        $sign_str = $recv['sysid'] . $recv['rps'] . $recv['timestamp'];
        $sign = $recv['sign'];
        $sign = urldecode($sign);
        $sign = str_replace(' ','+',$sign);

        if($this -> verify($sign_str,$sign)){
            \Neigou\Logger::General("tonglian.tonglian.is.verify.global", array("data"=>json_encode($recv)));

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
            $ret['pay_app_id'] = "mallinpayglobal";
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
            \Neigou\Logger::General("tonglian.tonglian.not.verify.global", array("data"=>json_encode($recv)));
            ob_clean();
            header("HTTP/1.1 400 Bad Request");
            $ret['message'] = 'Invalid Sign';
            $ret['status'] = 'invalid'; 
        }
        return $ret;
    }

    //生成签名
    private function sign($data) {
        $certs = array();
        openssl_pkcs12_read(file_get_contents(TONGLIAN_CERT_PATH . GLOBAL_TONGLIAN_PFX), $certs, GLOBAL_TONGLIAN_PASSWORD); //其中password为你的证书密码
        if(!$certs) return false;
        $signature = '';
        openssl_sign($data, $signature, $certs['pkey']);
        return base64_encode($signature);
    }
    //验证签名
    private function verify($data, $signature) {
        $signature = base64_decode($signature);
        $certs = array();
        openssl_pkcs12_read(file_get_contents(TONGLIAN_CERT_PATH . GLOBAL_TONGLIAN_PFX), $certs,  GLOBAL_TONGLIAN_PASSWORD);
        if(!$certs) return false;
        $result = (bool) openssl_verify($data, $signature, $certs['cert']); //openssl_verify验签成功返回1，失败0，错误返回-1
        return $result;
    }
}