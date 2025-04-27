<?php

/**
 * 我买网 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mwomai_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        \Neigou\Logger::General('ecstore.notify.mwomai',array('remark'=>'notify_param','data'=>$recv,'raw_data'=>$GLOBALS["HTTP_RAW_POST_DATA"],'request_data'=>$_REQUEST));
        #键名与pay_setting中设置的一致
        if($this->is_return_vaild($recv)){
            $payment_id = str_replace('NGDD','',$recv['out_trade_no']);
            $ret['payment_id'] = $payment_id;
            $ret['account'] = $recv['partner'].'-'.$recv['type'];
            $ret['bank'] = app::get('ectools')->_('我买网支付');
            $ret['pay_account'] = $recv['openid'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_fee']/100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_fee']/100;
            $ret['trade_no'] = $recv['notify_id'];//notify_id
            $ret['t_payed'] = strtotime($recv['time_end']);
            $ret['pay_app_id'] = 'mwomai';
            $ret['pay_type'] = 'online';
            if($recv['trade_state']=='0') {//为0 代表支付结果成功
                $this->msg(true);
                $ret['status'] = 'succ';
                //保存wmid
                $set['payment_id'] = $payment_id;
                $set['pay_account'] = $recv['wmid'];
                app::get('ectools')->model('payments')->save($set);
            }else{
                $this->msg(false);
                $ret['status'] = 'failed';
                \Neigou\Logger::General('ecstore.notify.mwomai',array('remark'=>'pay rzt err','data'=>$recv));
            }
        }else{
            $this->msg(false);
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mwomai',array('remark'=>'sign err','data'=>$recv));
        }
        return $ret;
    }

    public function redirect(){
        $req = $this->parset_str($_REQUEST['params']);
        $des = $this->decrypt($req,$this->getConf('code','wap_payment_plugin_mwxwomai'));
        $r = $this->convertUrlArray($des);
        $openId = $r['openId'];
        $_redis = kernel::single('base_sharedkvstore');
        if(!empty($openId)){
            //获取支付数据 跳转支付页面
            $payment['openId'] = $openId;

            $_redis->store('payment_mwxwomai_open_id_',trim($_REQUEST['payment_id']),$openId,300);
        } else {
            //设置微信支付参数为1
            $_redis->store('payment_mwxwomai_open_id_',trim($_REQUEST['payment_id']),1,300);
        }
        //跳转回原支付

        $payment_id = trim($_REQUEST['payment_id']);
        $payment_id = str_replace('NGDD','',$payment_id);
        $obj_payments = app::get('ectools')->model('order_bills');
        $sdf = $obj_payments->getRow('rel_id,money',array('bill_id'=>$payment_id));

        $data['order_id'] = $sdf['rel_id'];
        $data['combination_pay'] = false;
        //判断当前支付单号是否由微信小程序支付方式产生
        $fields = array();
        $_redis -> fetch('payment_mwxwomai_info_',$payment_id,$fields);
        if(!empty($fields)){
            if($fields['wxType']=='MINI'){
                $data['def_pay']['pay_app_id'] = 'mwxminiwomai';
            } else {
                $data['def_pay']['pay_app_id'] = 'mwxwomai';
            }
        } else {
            $data['def_pay']['pay_app_id'] = 'mwxwomai';
        }
        $data['def_pay']['cur_money'] = $sdf['money'];
        $query['payment'] = $data;
        //设置订单对应的payment_id 为当前
        $_redis->store('payment_mwxwomai_payment_id_',$sdf['rel_id'],$payment_id,300);
        $r =  $url = app::get('wap')->router()->gen_url(array('app'=>'b2c','ctl'=>'wap_paycenter2','act'=>'dopayment','arg0'=>'pop_order'));
        header('Location: '.$r.'?'.http_build_query($query));die;

    }

    /**
     * 获取openid-解析URL
     * @param $query
     * @return array
     */
    function convertUrlArray($query) {
        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }

    /**
     * 获取openid=格式化返回信息
     * @param $str
     * @return mixed
     */
    public function parset_str($str){
        //
        $str = str_replace('*','+',$str);
        $str = str_replace(':','/',$str);
        $str = str_replace('_','=',$str);
        return $str;

    }

    /**加密
     * @param $text string 文本内容
     * @param $key string 秘钥 max 24
     * @return string
     */
    public function encrypt($text,$key)
    {

        $iv   = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_TRIPLEDES,MCRYPT_MODE_ECB), MCRYPT_RAND);
        $text = $this->pkcs5Pad($text);
        $td = mcrypt_module_open(MCRYPT_3DES,'',MCRYPT_MODE_ECB,'');
        mcrypt_generic_init($td,$key,$iv);
        $data = base64_encode(mcrypt_generic($td, $text));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        print_r($data);
        return $data;

    }

    /**
     * 解密
     * @param $text
     * @param $key
     * @return bool|string
     */
    public function decrypt($text,$key)
    {
        $iv   = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_TRIPLEDES,MCRYPT_MODE_ECB), MCRYPT_RAND);
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
        mcrypt_generic_init($td, $key, $iv);
        $data  = $this->pkcs5UnPad(mdecrypt_generic($td, base64_decode($text)));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $data;
    }

    /**
     * @param $text
     * @return string
     */
    private function pkcs5Pad($text)
    {
        $pad = 8 - (strlen($text) % 8);
        return $text . str_repeat(chr($pad), $pad);
    }

    /**
     * @param $text
     * @return bool|string
     */
    private function pkcs5UnPad($text)
    {
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text)) return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
        return substr($text, 0, -1 * $pad);
    }




    public function query(){
        $payment_id = trim($_POST['out_trade_no']);
        $qr = false;
        if($payment_id[0]=='N'){
            //扫码订单
            $qr = true;
        }
        $payment_id = str_replace('NGDD','',$payment_id);
        $obj_payments = app::get('ectools')->model('payments');
        $sdf = $obj_payments->dump($payment_id, '*', '*');
        if($this->is_return_vaild($_POST)){
            if ($sdf){
                $out['errcode'] = 'SUCCESS';
                $out['errmsg'] = '成功';
                $out['body'] = 'Neigou-'.$sdf['payment_id'];
                $out['attach'] = $sdf['payment_id'];
                $out['out_trade_no'] = $sdf['payment_id'];
                if($qr){
                    $out['out_trade_no'] = 'NGDD'.$sdf['payment_id'];
                }
                $out['userID'] = $sdf['member_id'];
                $out['total_fee'] = number_format($sdf['cur_money'],2,".","")*100;
                $out['time_start'] = $sdf['t_begin'];
                $out['time_expire'] = $sdf['t_begin']+2380;
                $out['sign'] = $this->genSign($out);

            } else {
                $out['errcode'] = 'FAIL';
                $out['errmsg'] = '支付单查询失败';
            }
        } else {
            $out['errcode'] = 'FAIL';
            $out['errmsg'] = 'Sign Err';
        }
        \Neigou\Logger::General('ecstore.notify.mwomai.queryOrder',array('remark'=>'query_order','out'=>$out,'raw_data'=>$GLOBALS["HTTP_RAW_POST_DATA"],'request_data'=>$_REQUEST));
        echo json_encode($out);
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
        echo json_encode($data);
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
        $linkStr .= 'key='.$this->getConf('md5_key','wap_payment_plugin_mwomai');
        \Neigou\Logger::General('ecstore.mwomai.notify.linkStr',array('action'=>'make sign','linkStr'=>$linkStr,'sign'=>md5($linkStr)));
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