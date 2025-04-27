<?php

/**
 * 嘉宝支付处理 （委托内购管理支付）
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2018/05/14
 * Time: 11:23
 */

final class wap_payment_plugin_mjbalipay extends ectools_payment_app implements ectools_interface_payment_app {

    /**
     * @var string 支付方式名称
     */
    public $name = '嘉宝支付宝';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '嘉宝支付宝';
     /**
     * @var string 支付方式key
     */
    public $app_key = 'mjbalipay';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mjbalipay';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '嘉宝支付宝';
    /**
     * @var string 货币名称
     */
    public $curname = 'CNY';
    /**
     * @var string 当前支付方式的版本号
     */
    public $ver = '1.0';
    /**
     * @var string 当前支付方式所支持的平台
     */
    public $platform = 'iswap';

    /**
     * @var array 扩展参数
     */
    public $supportCurrency = array("CNY"=>"01");

    /**
     * @支付宝固定参数
     */
    public $Service_Paychannel = "mobile.merchant.paychannel";
    public $Service1 = "alipay.wap.trade.create.direct";    //接口1
    public $Service2 = "alipay.wap.auth.authAndExecute";    //接口2
    public $format = "xml";    //http传输格式
    public $sec_id = 'MD5';    //签名方式 不需修改
    public $_input_charset = 'utf-8';    //字符编码格式
    public $_input_charset_GBK = "GBK";
    public $v = '2.0';    //版本号
    public $gateway_paychannel="https://mapi.alipay.com/cooperate/gateway.do?";

    // todo v2.0
    // public $gateway="http://wappaygw.alipay.com/service/rest.htm?";

    // todo v2.2
    public $gateway = "https://mapi.alipay.com/gateway.do?";

    // todo v2.2
    public $service = "alipay.wap.create.direct.pay.by.user";




    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);

        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mjbalipay_server', 'callback');
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
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mjbalipay', 'callback');
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
        return '嘉宝支付宝';
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
                'title'=>app::get('wap')->_('商户号'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'priv_key'=>array(
                'title'=>app::get('wap')->_('私钥'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'pub_key'=>array(
                'title'=>app::get('wap')->_('支付宝公钥'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'seller_account_name'=>array(
                'title'=>app::get('wap')->_('支付宝账号'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'support_cur'=>array(
                'title'=>app::get('wap')->_('支持币种'),
                'type'=>'text hidden cur',
                'options'=>$this->arrayCurrencyOptions,
            ),
            'pay_desc'=>array(
                'title'=>app::get('wap')->_('描述'),
                'type'=>'html',
                'includeBase' => true,
            ),
            'pay_type'=>array(
                'title'=>app::get('wap')->_('支付类型(是否在线支付)'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('wap')->_('否'),'true'=>app::get('wap')->_('是')),
                'name' => 'pay_type',
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
        return app::get('wap')->_('嘉宝支付宝');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    // todo v2.2
    public function dopay($payment){
        $subject = '嘉宝精选';
        $spend = time()-$payment['create_time'];
        $expire = 2380-$spend;
        $price = number_format($payment['cur_money'],2,".","");
        $this->add_field('_input_charset','utf-8');
        $this->add_field('body','mjb内购订单');
        $this->add_field('notify_url',$this->notify_url);
        $this->add_field('out_trade_no',$payment['payment_id']);
        $this->add_field('partner',$this->getConf('mer_id',__CLASS__));
        $this->add_field('payment_type','1');
        $this->add_field('seller_id',$this->getConf('seller_account_name',__CLASS__));
        $this->add_field('service','mobile.securitypay.pay');
        $this->add_field('subject',$subject);
        $this->add_field('total_fee',$price);
        $sort_get = $this->arg_sort($this->fields);
        $my_sign = $this->build_rsa_mysign($sort_get);
        $this->add_field('sign',urlencode($my_sign));
        $this->add_field('sign_type','RSA');
        $req_param = $this->arg_sort($this->fields);
        $param_str =  $this->create_linkstring($req_param);
        $callback_url = $this->callback_url.'?order_sn='.$payment['payment_id'];
        \Neigou\Logger::General('mjbalipay.pay.req',array('param1'=>$param_str,'callback_url'=>$callback_url));
        echo $this->get_html($param_str,$callback_url);exit;
    }





    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Location:/m/paycenter2-result_pay-'.trim($recv['order_sn']).'.html?back=true');
    }




    /**
     * 校验方法
     * @param null
     * @return boolean
     */
    public function is_fields_valiad(){
        return true;
    }


    /**
     * 【新】生成支付表单 - 自动提交
     * @params null
     * @return null
     */
    public function get_html($str,$url) {
        $encodeType =  'utf-8';

        if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
            $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<script>
window.AndroidWebView.callAlipay('{$str}','{$url}');
</script>
</html>
eot;
        }else{
            $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<script>

setTimeout(function(){
    if(window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.callAlipay && window.webkit.messageHandlers.callAlipay.postMessage){
        window.webkit.messageHandlers.callAlipay.postMessage(['{$str}','{$url}'])
    }else{
        callAlipay('{$str}','{$url}')
    }
},1000);
setTimeout(function(){
                window.close();
                },5000)
</script>
</html>
eot;
        }


        return $html;
    }


    /**
     * 生成支付表单 - 自动提交
     * @params null
     * @return null
     */
    public function gen_form()
    {
      return '';
    }


//↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓公共函数部分↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓


    /**
     * RSA 签名计算
     * @param $data
     * @return mixed
     */
    public function build_rsa_mysign($data) {
        $data = $this->create_linkstring($data);
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->getConf('priv_key',__CLASS__), 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        openssl_sign($data, $sign, $res );
        $sign = base64_encode($sign);
        \Neigou\Logger::General('mjbalipay.pay.sign',array('data'=>$data,'sign'=>$sign));
        return $sign;
    }


    /**把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * $array 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    public function create_linkstring($array) {
        $arg  = "";
        while (list ($key, $val) = each ($array)) {
            $arg.=$key."=".$val."&";
        }
        $arg = substr($arg,0,count($arg)-2);             //去掉最后一个&字符
        return $arg;
    }





    /**对数组排序
     * $array 排序前的数组
     * return 排序后的数组
     */
    public function arg_sort($array) {
        ksort($array);
        reset($array);
        return $array;
    }





}
