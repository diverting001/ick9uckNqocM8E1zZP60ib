<?php
final class wap_payment_plugin_mallinpayjifen extends ectools_payment_app implements ectools_interface_payment_app {

    public $name = '通联积分支付';
    public $app_name = '通联积分支付';
    public $app_key = 'mallinpayjifen';
    public $app_rpc_key = 'mallinpayjifen';
    public $display_name = '通联积分支付';
    public $curname = 'CNY';
    public $ver = '1.0';
    public $platform = 'iswap';

    public $supportCurrency = array("CNY"=>"01");

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);

        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mallinpayjifen_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches))
        {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        }
        else
        {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mallinpayjifen', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->callback_url, $matches))
        {
            $this->callback_url = str_replace('http://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "http://" . $this->callback_url;
        }
        else
        {
            $this->callback_url = str_replace('https://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "https://" . $this->callback_url;
        }
        $this->submit_url = $this->gateway . '_input_charset=' . $this->_input_charset;
        $this->submit_method = 'GET';
        $this->submit_charset = $this->_input_charset;
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        return "通联钱包积分支付移动端";
    }

     /**
     * 后台配置参数设置
     * @param null
     * @return array 配置参数列表
     */
    public function setting(){
        return array(
            'pay_name'=>array(
                'title'=>app::get('wap')->_('支付方式名称'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'mer_id'=>array(
                'title'=>app::get('wap')->_('合作者身份(parterID)'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'status'=>array(
                'title'=>app::get('wap')->_('是否开启此支付方式'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('wap')->_('否'),'true'=>app::get('wap')->_('是')),
                'name' => 'status',
            ),
        );
    }

    /**
     * 前台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function intro(){
        return "通联钱包积分支付";
    }

    /**
     * 提交支付信息的接口
     * @param $payment array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment){
        $mer_id = $this->getConf('mer_id', __CLASS__);
        $_third_member = app::get('b2c') -> model('third_members');
        $third_member_info = $_third_member -> getRowByFiled(array('channel' => 'tonglian','internal_id' => $payment['member_id']));
        if(!$third_member_info || !$third_member_info[0]['external_bn']){
            header('Location: /m/paycenter2-' . $payment['order_id'] . '-true.html');
            exit;
        }
        $goods_code = array();
        $order_items = kernel::single("b2c_service_order")->getOrderInfo($payment['order_id']);
        $goods_code_item = array();
        foreach ($order_items['items'] as $k => $v) {
            $v['price'] = $v['price'] * 100;
            $goods_code_item[] = $v['bn'] . '|' . $v['price'] . '|' . '|' . '内购网' . '|' . '|' . '|';
        }
        $goods_code['goodsCode'] = urlencode(implode(';',$goods_code_item));

        //商品名称设置
        reset($order_items['items']);
        $order_item = current($order_items['items']);
        $product_name = $order_item['name'];
        if(count($order_items['items']) > 1){
            $product_name .= '等';
        }
        $price = number_format($payment['cur_money'],2,".","");
        $_timestamp = date('Y-m-d H:i:s');
        $order_detail = kernel::base_url(1) . '/m/member-new_orderdetail-' . $payment['order_id'] . '.html';
        $data = array();
        $data['service'] = 'PayService';
        $data['method'] = 'pay';
        $data['param']['frontUrl'] = $this->callback_url;
        $data['param']['backUrl'] = $this->notify_url;
        $data['param']['language'] = 1;
        $data['param']['productName'] = $product_name;
        // $data['param']['showUrl'] = $order_detail;//kernel::base_url(1) . '/m/cart.html';
        $data['param']['orderInfoUrl'] = $order_detail;
        $data['param']['orderNo'] = $payment['payment_id'];
        $data['param']['orderMoney'] = $price * 100;
        $data['param']['orderDatetime'] = date('Y-m-d H:i:s',$order_items['create_time']);
        $data['param']['ordErexpireDatetime'] = date('Y-m-d H:i:s',$order_items['create_time'] + 2400);
        $data['param']['transactionType'] = 2;
        // $data['param']['productName'] = $subject;
        $data['param']['bizBuyerUserId'] = $payment['member_id'];
        $data['param']['buyerUserId'] = $third_member_info[0]['external_bn'];
        $data['param']['sellerUserId'] = $mer_id;
        $data['param']['industryCode'] = 2522;
        $data['param']['industryName'] = '其他';
        $data['param']['orderSkipUrl'] = OPENAPI_DOMAIN . '/ChannelInterop/V1/TongLian/Web/getUserInfo';
        $data['param']['ext'] = urldecode(json_encode($goods_code));
        $param = array();
        $param['sysid'] = $mer_id;
        $param['timestamp'] = $_timestamp;
        $param['v'] = '1.0';
        $param['req'] = $this -> array2json($data);
        $sign_str = $mer_id . $param['req'] . $param['timestamp'];
        $param['sign'] = $this -> sign($sign_str);
        \Neigou\Logger::General("tonglian.tonglian.req", array("data"=>json_encode($param)));
        ob_clean();
        echo $this -> array2json($param);
        exit;
    }

    // 不转义 / 以及json转字符串
    function array2json($arr) { 
        $parts = array();
        $is_list = false; 
        $keys = array_keys($arr);
        $max_length = count($arr)-1; 
        if(($keys[0] == 0) and ($keys[$max_length] == $max_length)) {//See if the first key is 0 and last key is length - 1 
            $is_list = true; 
            for($i=0; $i<count($keys); $i++) { //See if each key correspondes to its position 
                if($i != $keys[$i]) { //A key fails at position check. 
                    $is_list = false; //It is an associative array. 
                    break; 
                } 
            } 
        } 
        foreach($arr as $key=>$value) {
            if(is_array($value)) { //Custom handling for arrays 
                if($is_list) $parts[] = $this -> array2json($value); /* :RECURSION: */ 
                else $parts[] = '"' . $key . '":' . $this -> array2json($value); /* :RECURSION: */ 
            } else { 
                $str = ''; 
                if(!$is_list) $str = '"' . $key . '":'; 
                //Custom handling for multiple data types
                if(is_numeric($value) && ($key == 'orderMoney' || $key == 'transactionType')) $str .= $value; //Numbers 
                elseif($value === false) $str .= 'false'; //The booleans 
                elseif($value === true) $str .= 'true'; 
                else $str .= '"' . addslashes($value) . '"'; //All other things 
                // :TODO: Is there any more datatype we should be in the lookout for? (Object?) 
                $parts[] = $str;
            } 
        } 
        $json = implode(',',$parts); 
         
        if($is_list) return '[' . $json . ']';//Return numerical JSON 
        return '{' . $json . '}';//Return associative JSON 
    } 


    /**
     * 验证提交表单数据的正确性
     * @params null
     * @return boolean 
     */
    public function is_fields_valiad(){
        return true;
    }

    public function callback(&$recv){
        $ret = array();
        $ret['callback_source'] = 'client';
        \Neigou\Logger::General("callback.tonglianjifen.recv", array("data"=>$recv));
        $sign_str = $recv['sysid'] . $recv['rps'] . $recv['timestamp'];
        $sign = $recv['sign'];
        $sign = urldecode($sign);
        $sign = str_replace(' ','+',$sign);
        $mer_id = $this->getConf('mer_id', __CLASS__);
        if(!$this -> verify($sign_str,$sign)){
            $ret['status'] = 'failed';
            return $ret;  
        }
        $recv['rps'] = json_decode($recv['rps'],true);
        if(!$recv['rps']['returnValue']['orderNo']){
            \Neigou\Logger::General("callback.tonglianjifen.fail", array('remark'=>'no orderNo',"data"=>$recv));
            $ret['status'] = 'failed';
            return $ret;
        }

        if(!$recv['rps']['returnValue']['orderMoney']){
            \Neigou\Logger::General("callback.tonglianjifen.fail", array('remark'=>'no orderMoney',"data"=>$recv));
            $ret['status'] = 'failed';
            return $ret;
        }

        if(!$recv['rps']['status'] != 'OK'){
            \Neigou\Logger::General("callback.tonglianjifen.fail", array('remark'=>'pay fail',"data"=>$recv));
            $ret['status'] = 'failed';
            return $ret; 
        }
        $ret['payment_id'] = $recv['rps']['returnValue']['orderNo'];
        $ret['account'] = $mer_id;
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
        $ret['status'] = 'succ';
        \Neigou\Logger::General("callback.tonglianjifen.succ", array('remark'=>'succ',"data"=>$recv,'ret'=>$ret));
        return $ret;
    }

    /**
     * 生成支付表单 - 自动提交(点击链接提交的那种方式，通常用于支付方式列表)
     * @params null
     * @return null
     */
    public function gen_form(){
        echo '';
    }

    //生成签名
    private function sign($data) {
        $certs = array();
        openssl_pkcs12_read(file_get_contents(TONGLIAN_JF_CERT_PATH . TONGLIAN_JF_PFX), $certs, TONGLIAN_JF_PASSWORD); //其中password为你的证书密码
        if(!$certs) return false;
        $signature = '';
        openssl_sign($data, $signature, $certs['pkey']);
        return base64_encode($signature);
    }

    //验证签名
    private function verify($data, $signature) {
        $signature = base64_decode($signature);
        $certs = array();
        //积分支付验证
        openssl_pkcs12_read(file_get_contents(TONGLIAN_JF_CERT_PATH . TONGLIAN_JF_PFX), $certs,  TONGLIAN_JF_PASSWORD);
        if(!$certs) return false;
        $result = (bool) openssl_verify($data, $signature, $certs['cert']); //openssl_verify验签成功返回1，失败0，错误返回-1
        return $result;
    }
}