<?php

/**
 * Class wap_payment_plugin_mallinpayhtml
 * 通联H5支付
 * 武传斌
 */
final class wap_payment_plugin_mallinpayhtml extends ectools_payment_app implements ectools_interface_payment_app {

    public $name = '通联h5支付';
    public $app_name = '通联h5支付';
    public $app_key = 'mallinpayhtml';
    public $app_rpc_key = 'mallinpayhtml';
    public $display_name = '通联h5支付';
    public $curname = 'CNY';
    public $ver = '1.0';
    public $platform = 'iswap';

    public $supportCurrency = array("CNY"=>"01");

    /**
     * @var array 展示的环境[h5 weixin wxwork]
     */
    public $display_env = array('weixin');

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);

        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mallinpayhtml_server', 'callback');
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
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mallinpayhtml', 'callback');
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
        $this->submit_url = $this->getConf('submit_url', __CLASS__);
        $this->submit_method = 'GET';
        $this->submit_charset = $this->_input_charset;
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        return "通联钱包h5支付";
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
                'title'=>app::get('wap')->_('商户ID'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'cert_path'=>array(
                'title'=>app::get('wap')->_('私钥证书路径'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'cert_password'=>array(
                'title'=>app::get('wap')->_('私钥证书密码'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'cert_pub_path'=>array(
                'title'=>app::get('wap')->_('公钥证书路径'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'pay_type'=>array(
                'title'=>app::get('wap')->_('支付类型(是否在线支付)'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('wap')->_('否'),'true'=>app::get('wap')->_('是')),
                'name' => 'pay_type',
            ),
            'submit_url'=>array(
                'title'=>app::get('wap')->_('支付网关地址'),
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
        return "通联钱包h5端";
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment){
        $mer_id = $this->getConf('mer_id', __CLASS__);
        $price = number_format($payment['cur_money'],2,".","");

//        $_order = app::get('b2c') -> model('orders');
//        $order_info = $_order -> getRow('*',array('order_id' => $payment['order_id']));
        $order_info = kernel::single("b2c_service_order")->getOrderInfo($payment['order_id']);


        $spend_time = time()-$order_info['create_time'];
        $expir_time = floor((2100-$spend_time)/60);
        //针对通联需求 添加用户UID
        $members_mdl = app::get('b2c')->model('third_members');
        $member_id = $_SESSION['account'][pam_account::get_account_type('b2c')];
        $member_info = $members_mdl->getRow("*", array("internal_id"=>$member_id,'source' => 1));

        //商品名称设置
        reset($order_info['items']);
        $order_item = current($order_info['items']);
        $product_name = $order_item['name'];
        if(count($order_info['items']) > 1){
            $product_name .= '等';
        }

        $params = array(

            //以下信息非特殊情况不需要改动
            'inputCharset' => 1,                 //默认填1；1代表UTF-8、2代表GBK、3代表GB2312；
            'pickupUrl' =>  $this->callback_url,  //前台通知地址
            'receiveUrl' => $this->notify_url,	  //后台通知地址
            'version' => '1.0',				  //版本
            'merchantId' => $mer_id,		// 商户号
            'merchantUserId' => $payment['member_id'],		// 商户系统用户编号
            'orderNo' => $payment['payment_id'],	//商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
            'orderAmount' => $price*100,	//交易金额，单位分，此处默认取demo演示页面传递的参数
            'orderCurrency' => '0',	          //交易币种，境内商户固定156
            'orderDatetime' => date('YmdHis',time()),	//订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
            'orderExpireDatetime' => $expir_time,//TODO 统一进行取整处理
            'productName' => $product_name,
             'ext1' => '<USER>'.$member_info['external_bn'].'</USER>',
//            'payType' => 37,
        );


        $str = $this->createLinkString($params,false,false);
        $params['signMsg'] = $this->sign($str);
        \Neigou\Logger::General('ecstore.ectools.mallinpayhtml',
            array(
                "pay_method"=>"mallinpayhtml1",
                "str" => $str,
                "params" => $params,
                "remark"=>'str'
            ));

        \Neigou\Logger::General('ecstore.ectools.mallinpayhtml',
            array(
                "pay_method"=>"mallinpayhtml1",
                "sign" => $params['signMsg'] ,
                "remark"=>'sign'
            ));

        echo $this->make_form($params);die;


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
        header('Content-Type:text/html; charset=utf-8');
        //测试数据
//        echo 1;

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
        //先要组合排序的内容
        $ret['callback_source'] = 'client';
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

        \Neigou\Logger::General('ecstore.ectools.mallinpayhtml',
            array(
                "pay_method"=>"mallinpayhtml1",
                "trade_no" => $recv['orderNo'],
                "recv"=>$recv,
                "remark"=>'recv'
            ));

        $sign = $recv['signMsg'];
        unset($recv['signMsg']);
        //开始组合字符串
        $str = $this->createLinkString($recv1,false,false);
        \Neigou\Logger::General('ecstore.ectools.mallinpayhtml',
            array(
                "pay_method"=>"mallinpayhtml1",
                "link"=>$str,
                "remark"=>'strlink'
            ));
        //验证签名

        if($this -> verify($str,$sign)){
            \Neigou\Logger::General("tonglianh5.verify.ok", array("message"=>json_encode($recv)));
            $ret['payment_id'] = $recv['orderNo'];
            $ret['account'] = $recv['merchantId'];
            $ret['bank'] = app::get('wap')->_('通联微信端口支付');
            $ret['pay_account'] = app::get('wap')->_('付款帐号');
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['payAmount'] / 100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['payAmount'] / 100;
            $ret['trade_no'] = $recv['paymentOrderId'];
            $ret['t_payed'] = (strtotime($recv['payDatetime']) ? strtotime($recv['payDatetime']) : time());
            $ret['pay_app_id'] = "mallinpayhtml";
            $ret['pay_type'] = 'online';
            $ret['memo'] = '';
            \Neigou\Logger::General('ecstore.ectools.mallinpayhtml',
                array(
                    "pay_method"=>"mallinpayhtml1",
                    'ret'=>$ret,
                    "remark"=>'ret'
                ));
            if($recv['payResult'] == '1'){
                \Neigou\Logger::General('ecstore.ectools.mallinpayhtml',
                    array(
                        "pay_method"=>"mallinpayhtml1",
                        "remark"=>'status-succ'
                    ));
                $ret['status'] = 'succ';
            }else{
                \Neigou\Logger::General('ecstore.ectools.mallinpayhtml',
                    array(
                        "pay_method"=>"mallinpayhtml1",
                        "remark"=>'status-fail'
                    ));
                $ret['status'] = 'failed';
            }
        }else{
            \Neigou\Logger::General('ecstore.ectools.mallinpayhtml',
                array(
                    "pay_method"=>"mallinpayhtml1",
                    "remark"=>'status-invalid'
                ));
            $ret['status'] = 'invalid';
        }
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

    public function make_form($params){
        $encodeType =  'utf-8';
        $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<body onload="javascript:document.pay_form.submit();">
    <form id="pay_form" name="pay_form" action="{$this->submit_url}" method="post">

eot;
        foreach ( $params as $key => $value ) {
            $html .= "    <input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
        }
        $html .= <<<eot
   <!-- <input type="submit" type="hidden">-->
    </form>
</body>
</html>
eot;
        return $html;
    }

    /**
     * 讲数组转换为string
     *
     * @param $para 数组
     * @param $sort 是否需要排序
     * @param $encode 是否需要URL编码
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
            if(strlen($value)>0) {
                $linkString .= $key . "=" . $value . "&";
            }
        }
        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
        return $linkString;
    }

    //生成签名
    private function sign($data) {
        $certs = array();
        openssl_pkcs12_read(file_get_contents($this->getConf('cert_path', __CLASS__)), $certs, $this->getConf('cert_password', __CLASS__)); //其中password为你的证书密码
        if(!$certs) return false;
        $signature = '';
        openssl_sign($data, $signature, $certs['pkey']);
        return base64_encode($signature);
    }
    //验证签名
    private function verify($data, $sign) {
        $sign = urldecode($sign);
        $sign = str_replace(' ','+',$sign);
        $sign = base64_decode($sign);
        $key = openssl_pkey_get_public(file_get_contents($this->getConf('cert_pub_path', __CLASS__)));
        $result = openssl_verify($data, $sign, $key, OPENSSL_ALGO_SHA1) === 1;
        return $result;
    }
}