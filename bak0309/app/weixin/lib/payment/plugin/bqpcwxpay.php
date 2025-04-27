<?php
final class weixin_payment_plugin_bqpcwxpay extends ectools_payment_app implements ectools_interface_payment_app {

    /**
     * @var string 支付方式名称
     */
    public $name = '微信支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '微信支付';
     /**
     * @var string 支付方式key
     */
    public $app_key = 'bqpcwxpay';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'bqpcwxpay';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '微信支付';
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
    public $platform = 'ispc';

    /**
     * @var array 扩展参数
     */
    public $supportCurrency = array("CNY"=>"01");

    /**
     * @var string 通用支付
     */
    public $is_general = 1;


    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);

         $this->notify_url = kernel::openapi_url('openapi.weixin', 'bqpcwxpay');
        //$this->notify_url = kernel::base_url(1).'/openapi/weixin/wxpay';
        #test
        // $this->notify_url = kernel::base_url(1).'/index.php/wap/paycenter-wxpay.html';
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
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/weixin/weixin_payment_plugin_bqpcwxpay', 'callback');
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
        // $this->submit_url = $this->gateway;
        // $this->submit_method = 'GET';
        $this->submit_charset = 'UTF-8';
        $this->signtype = 'MD5';
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        $regIp = isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:$_SERVER['HTTP_HOST'];
        return '<img src="' . app::get('weixin')->res_url . '/payments/images/WXPAY.jpg"><br /><b style="font-family:verdana;font-size:13px;padding:3px;color:#000"><br>微信支付是由腾讯公司知名移动社交通讯软件微信及第三方支付平台财付通联合推出的移动支付创新产品，旨在为广大微信用户及商户提供更优质的支付服务，微信的支付和安全系统由腾讯财付通提供支持。</b>';
    }

     /**
     * 后台配置参数设置
     * @param null
     * @return array 配置参数列表
     */
    public function setting(){
        // 公众账号
        $publicNumbersInfo = app::get('weixin')->model('bind')->getList('appid,name',array('appid|noequal'=>''));
        $publicNumbers = array();
        foreach($publicNumbersInfo as $row){
            $publicNumbers[$row['appid']] = $row['name'];
        }
        return array(
            'pay_name'=>array(
                'title'=>app::get('weixin')->_('支付方式名称'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'appId'=>array(
                'title'=>app::get('weixin')->_('选择公众账号'),
                'type'=>'select',
                'options'=>$publicNumbers
            ),
            'cert_path'=>array(
                'title'=>app::get('weixin')->_('证书路径'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
           /* 'appId'=>array(
                'title'=>app::get('weixin')->_('appId'),
                'type'=>'string',
                'validate_type' => 'required',
            ),*/
        	'partnerId'=>array(
        		'title'=>app::get('weixin')->_('商户ID'),
        		'type'=>'string',
        		'validate_type' => 'required',
        	),
            'paySignKey'=>array(
                'title'=>app::get('weixin')->_('支付接口密钥'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'order_by' =>array(
                'title'=>app::get('ectools')->_('排序'),
                'type'=>'string',
                'label'=>app::get('ectools')->_('整数值越小,显示越靠前,默认值为1'),
            ),
            /*'appSecret'=>array( // app支付使用
                'title'=>app::get('weixin')->_('appSecret'),
                'type'=>'string',
                'validate_type' => 'required',
            ),*/
           
            /*'partnerKey'=>array(
                'title'=>app::get('weixin')->_('partnerKey'),
                'type'=>'string',
                'validate_type' => 'required',
            ),*/
            'support_cur'=>array(
                'title'=>app::get('weixin')->_('支持币种'),
                'type'=>'text hidden cur',
                'options'=>$this->arrayCurrencyOptions,
            ),
            'pay_desc'=>array(
                'title'=>app::get('weixin')->_('描述'),
                'type'=>'html',
                'includeBase' => true,
            ),
            'pay_type'=>array(
                'title'=>app::get('weixin')->_('支付类型(是否在线支付)'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('weixin')->_('否'),'true'=>app::get('weixin')->_('是')),
                'name' => 'pay_type',
            ),
            'is_general'=>array(
                'title'=>app::get('ectools')->_('通用支付(是否为缺省通用支付)'),
                'type'=>'radio',
                'options'=>array('0'=>app::get('ectools')->_('否'),'1'=>app::get('ectools')->_('是')),
            ),
            'status'=>array(
                'title'=>app::get('weixin')->_('是否开启此支付方式'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('weixin')->_('否'),'true'=>app::get('weixin')->_('是')),
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
        return app::get('weixin')->_('微信支付是由腾讯公司知名移动社交通讯软件微信及第三方支付平台财付通联合推出的移动支付创新产品，旨在为广大微信用户及商户提供更优质的支付服务，微信的支付和安全系统由腾讯财付通提供支持。财付通是持有互联网支付牌照并具备完备的安全体系的第三方支付平台。');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment)
    {
        $this->qr_paymemt($payment);
    }


    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    function callback(&$in){
        $appId      = trim($this->getConf('appId',      __CLASS__)); // appid 公众号 ID
        $paySignKey = trim($this->getConf('paySignKey', __CLASS__)); // PaySignKey 对应亍支付场景中的 appKey 值
        // $appSecret  = $this->getConf('appSecret',  __CLASS__); // app支付时使用
        $partnerId  = trim($this->getConf('partnerId',  __CLASS__)); // 商户ID
        //$partnerKey = trim($this->getConf('partnerKey', __CLASS__)); // 财付通商户权限密钥 Key

        $postData = $in['weixin_postdata'];
        unset($in['weixin_postdata']);

        ksort($postData);
        $unSignParaString = weixin_commonUtil::formatQueryParaMap($postData, false);
        $checksign = weixin_commonUtil::verifySignature($unSignParaString, $postData['sign'], $paySignKey);
        $objMath = kernel::single('ectools_math');
        $ret = array();
        $ret['payment_id'] = $postData['out_trade_no'];
        $ret['account'] = $postData['openid'];
        $ret['bank'] = app::get('weixin')->_('微信支付').$postData['bank_type'];
        $ret['pay_account'] = app::get('weixin')->_('付款帐号');
        $ret['currency'] = 'CNY';
        $ret['money'] = $objMath->number_multiple(array($postData['total_fee'], 0.01));
        $ret['paycost'] = '0.000';
        $ret['cur_money'] = $objMath->number_multiple(array($postData['total_fee'], 0.01));
        $ret['trade_no'] = $postData['transaction_id'];
        $ret['t_payed'] = strtotime($postData['time_end']);
        $ret['pay_app_id'] = "bqpcwxpay";
        $ret['pay_type'] = 'online';
        $ret['memo'] = '微信交易单号:'.$postData['transaction_id'];
        $ret['thirdparty_account'] = $postData['openid'];

        //校验签名
        if ( $checksign && ($postData['result_code']=="SUCCESS" or $postData['return_code']=="SUCCESS") ) {
             $ret['status'] = 'succ';
        }else{
            $ret['status']='failed';
        }

        return $ret;
    }

    /**
     * 支付成功回打支付成功信息给支付网关
     */
    function ret_result($paymentId){
        echo 'success';exit;
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
     * 生成支付表单 - 自动提交
     * @params null
     * @return null
     */
    public function gen_form(){
        return '';
    }

    private function qr_paymemt($payment) {
        $appId      = trim($this->getConf('appId',      __CLASS__)); // appid 公众号 ID
        $paySignKey = trim($this->getConf('paySignKey', __CLASS__)); // PaySignKey 对应亍支付场景中的 appKey 值
        $partnerId  = trim($this->getConf('partnerId',  __CLASS__)); // 商户ID


        $this->add_field("bank_type"          , "WX" );
        $body = strval( str_replace(' ', '', (isset($payment['body']) && $payment['body']) ? $payment['body'] : app::get('weixin')->_('内购网订单') ) ) ;
        $this->add_field("body"               , $this->subtext($body,30));
        $this->add_field("mch_id"             , strval( $partnerId ) );
        $this->add_field("out_trade_no"       , strval( $payment['payment_id'] ) );
        $this->add_field("total_fee"          , strval( bcmul($payment['cur_money'], 100) ) );//@TODO maojz 使用高精度乘法函数替换
        $this->add_field("fee_type"           , "1" );
        $this->add_field("notify_url"         , strval( $this->notify_url ) );
        $this->add_field("spbill_create_ip"   , strval( $payment['ip'] ) );
        $this->add_field("input_charset"      , "UTF-8" );
        $this->add_field('trade_type'         , "NATIVE");
        //海外购订单增加实名信息
        if($payment['is_certification']){
            $this->add_field('user_creid',$payment['card_id']);
            $this->add_field('user_truename',$payment['card_name']);
            \Neigou\Logger::General('ecstore.bqpcwxpay.global',array('remark'=>'海外购订单','card_id'=>$payment['card_id'],'card_name'=>$payment['card_name'],'payment'=>$payment));
        }
        $prepay_info = weixin_commonUtil::getPrepayInfo($appId,$paySignKey,$this->fields);
        \Neigou\Logger::General("pcwxpay.qr_paymemt", array("action"=>"getPrepayInfo", "weixinfields" => json_encode($this->fields),"prepay_info" => json_encode($prepay_info)));
        if (empty($prepay_info['code_url'])) {
            \Neigou\Logger::General('ecstore.bqpcwxpay',array('remark'=>'获取code_url失败','request_data'=>json_encode($this->fields),'order_id'=>$payment['order_id'],'payment'=>$payment));
            die("获取统一支付code_url错误");
        }
        $qr_pay_url = app::get('site')->router()->gen_url(array('app'=>'weixin','ctl'=>'site_wxin','act'=>'qr_pay',
            'full'=>1,'arg0'=>base64_encode($prepay_info['code_url']), 'arg1'=>$payment['order_id'], 'arg2'=>$payment['cur_money']));
        header('Location: '.$qr_pay_url);
        exit;
    }
}
