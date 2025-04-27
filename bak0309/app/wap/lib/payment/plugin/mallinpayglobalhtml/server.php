<?php
/*
 * mallinpay server callback
 *
*/

class wap_payment_plugin_mallinpayglobalhtml_server extends ectools_payment_app {



    public function callback(&$recv){
        header('Content-Type:text/html; charset=utf-8');
        //测试数据

//        $recS = '{
//	"merchantId": "008210249000001",
//	"version": "1.0",
//	"paymentOrderId": "201708211843381966",
//	"orderNo": "15033123311792",
//	"orderDatetime": "20170821184531",
//	"orderAmount": "1",
//    "payDatetime": "20170821184351",
//    "payAmount": "1",
//    "ext1": "",
//    "ext2": "",
//    "payResult": "1",
//    "errorCode": "",
//    "returnDatetime": "20170821184428",
//    "signMsg": "LJOjPs47oFb9GWUdnbNdhYA6BjVNIHpfT6EXZXgNnfNLqG8lBWkpuxRVjXPf9ZxlZNuRf1ZxeE0XTLhjmPflSYLZ5KLwxJfzS+UI+sa6sl7oEnbSckoYMq1D5yW8UIPQD2x4U3E2PuVOaOUtlvCS5BNO8TcOIcjXEzyVUhg1Sho="
//}';
//        $recv = json_decode($recS,true);
        $ret['callback_source'] = 'server';
        //先要组合排序的内容
        $recv1["merchantId"] = $recv['merchantId'];
        $recv1["version"] = $recv['version'];
        $recv1["paymentOrderId"] = $recv['paymentOrderId'];
        $recv1["orderNo"] = $recv['orderNo'];
        $recv1["orderDatetime"] = $recv['orderDatetime'];
        $recv1["orderAmount"] = $recv['orderAmount'];
        $recv1["payDatetime"] = $recv['payDatetime'];
        $recv1["payAmount"] = $recv['payAmount'];
        $recv1["ext1"] = $recv['ext1'];
        $recv1["ext2"] = $recv['ext2'];
        $recv1["payResult"] = $recv['payResult'];
        $recv1["errorCode"] = $recv['errorCode'];
        $recv1["returnDatetime"] = $recv['returnDatetime'];
//        $recv1["signMsg"] = $recv['signMsg'];
//print_r($recv1);die;
        $sign = $recv['signMsg'];
        unset($recv['signMsg']);
        //开始组合字符串
        $str = $this->createLinkString($recv1,false,false);
//echo $str;die;
        //验证签名

        if($this -> verify($str,$sign)){
            \Neigou\Logger::General("tonglianh5.verify.ok", array("message"=>json_encode($recv)));
            $ret['payment_id'] = $recv['orderNo'];
            $ret['account'] = $recv['merchantId'];
            $ret['bank'] = app::get('wap')->_('通联国际微信端口支付');
            $ret['pay_account'] = app::get('wap')->_('付款帐号');
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['payAmount'] / 100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['payAmount'] / 100;
            $ret['trade_no'] = $recv['paymentOrderId'];
            $ret['t_payed'] = (strtotime($recv['payDatetime']) ? strtotime($recv['payDatetime']) : time());
            $ret['pay_app_id'] = "mallinpayglobalhtml";
            $ret['pay_type'] = 'online';
            $ret['memo'] = '';
            if($recv['payResult'] == '1'){
                ob_clean();
                header('HTTP/1.1 200 OK');
                echo "验证签名通过";
                $ret['status'] = 'succ';
            }else{
                ob_clean();
                header("HTTP/1.1 400 Bad Request");
                echo "验证签名不通过";
                $ret['status'] = 'failed';
            }
        }else{
            \Neigou\Logger::General("tonglianh5.verify.fail", array("message"=>json_encode($recv)));
            ob_clean();
            header("HTTP/1.1 400 Bad Request");
            $ret['message'] = 'Invalid Sign';
            echo "验证签名不通过";
            $ret['status'] = 'invalid';
        }

//        print_r($ret);die;
        return $ret;

    }

    /**
     * 数组转换为string
     *
     * @param $para 数组
     * @param $sort 是否需要排序
     * @param $encode 是否需要URL编码
     * @author 武传斌
     * @return string
     */
    function createLinkString($para, $sort, $encode) {
        if($para == NULL || !is_array($para))
            return "";
        $linkString = "";
        if ($sort) {
            $para = argSort ( $para );
        }
        while ( list ( $key, $value ) = each ( $para ) ) {
            if ($encode) {
                $value = urlencode ( $value );
            }
            if(strlen($value)>0){
                $linkString .= $key . "=" . $value . "&";
            }

        }
        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
        return $linkString;
    }

    //验证签名
    private function verify($data, $sign) {
        $sign = str_replace(' ','+',$sign);
        $sign = base64_decode($sign);
        $key = openssl_pkey_get_public(file_get_contents($this->getConf('cert_pub_path', 'wap_payment_plugin_mallinpayglobalhtml')));
        $result = openssl_verify($data, $sign, $key, OPENSSL_ALGO_SHA1) === 1;
        return $result;
    }
}