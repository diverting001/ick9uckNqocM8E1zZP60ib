<?php

/**
 * unicom支付 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_municom_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        //替换请求串中的$为 &
        $tmp_str = str_replace('$','&',$recv['param']);
        parse_str($tmp_str,$arr);
        //获取sign信息
        $sign = $arr['signmsg'];
        //去掉不参与签名的信息
        unset($arr['hmac']);
        unset($arr['signmsg']);
        //创建签名串
        $data = $this->_create_link_string($arr,true,false);
        $pub_key = file_get_contents($this->getConf('cer_path','wap_payment_plugin_municom'));
        $key = openssl_get_publickey($pub_key);
        $sign = str_replace(' ','+',$sign);
        $res = (bool)openssl_verify($data,base64_decode($sign),$key,'SHA256');
        if($res){
            //签名验证通过
            $ret['payment_id'] = $arr['orderid'];
            $ret['account'] = $arr['payfloodid'];
            $ret['bank'] = app::get('ectools')->_('联通沃支付');
            $ret['pay_account'] = app::get('wap')->_('付款帐号');
            $ret['currency'] = 'CNY';
            $ret['money'] = $arr['paybalance']/100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $arr['paybalance']/100;
            $ret['trade_no'] = $arr['payfloodid'];//JD 交易流水号为上报订单号 对接人称此号 在JD系统唯一
            $ret['t_payed'] = strtotime($arr['resptime']);
            $ret['pay_app_id'] = "municom";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            \Neigou\Logger::General('ecstore.notify.mnicom', array('remark' => 'trade_succ', 'data' => $ret));
            echo 'SUCCESS';
        } else {
            echo 'SIGN ERR';
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mnicom',array('remark'=>'sign_err','data'=>$recv));
        }
        return $ret;
    }

    /**
     * 【新】将数组转换成String
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
            if(!empty($value)){
                $linkString .= $key  .'='. $value.'|' ;
            }

        }
        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
        //添加key
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