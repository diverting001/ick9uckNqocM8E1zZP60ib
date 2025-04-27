<?php

/**
 * 银联支付 WAP
 * @auther wuchuabin <wuchuanbin3013@163.com>
 * @version 0.1
 * @package ectools.lib.payment.plugin
 */
final class wap_payment_plugin_munionpay extends ectools_payment_app implements ectools_interface_payment_app {

    /**
     * @var string 支付方式名称
     */
    public $name = '银联支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '银联支付接口';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'munionpay';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'munionpay';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '银联支付';
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
     * @var string 通用支付
     */
    public $is_general = 1;

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
//        echo 555;die;
        parent::__construct($app);

        //$this->callback_url = $this->app->base_url(true)."/apps/".basename(dirname(__FILE__))."/".basename(__FILE__);
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_munionpay_server', 'callback');
//        echo $this->notify_url;die;
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
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_munionpay', 'callback');
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
//        print_r($this);die;
        //$this->submit_url = 'https://www.alipay.com/cooperate/gateway.do?_input_charset=utf-8';
        //ajx  按照相应要求请求接口网关改为一下地址
        $this->submit_url = $this->getConf('submit_url', __CLASS__);
        $this->submit_method = 'POST';
        $this->submit_charset = 'utf-8';

    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        return '银联支付MOBILE配置信息';
    }

    /**
     * 后台配置参数设置
     * @param null
     * @return array 配置参数列表
     */
    public function setting(){
        return array(
            'pay_name'=>array(
                'title'=>app::get('ectools')->_('支付方式名称'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'mer_id'=>array(
                'title'=>app::get('ectools')->_('合作者身份(parterID)'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'root_cert' =>array(
                'title'=>app::get('ectools')->_('根证书路径'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'middle_cert' =>array(
                'title'=>app::get('ectools')->_('中级证书路径'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'pfx_cert' =>array(
                'title'=>app::get('ectools')->_('pfx证书路径'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'pfx_pass' =>array(
                'title'=>app::get('ectools')->_('pfx证书密码'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('支付请求API'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'order_by' =>array(
                'title'=>app::get('ectools')->_('排序'),
                'type'=>'string',
                'label'=>app::get('ectools')->_('整数值越小,显示越靠前,默认值为1'),
            ),
            'is_general'=>array(
                'title'=>app::get('ectools')->_('通用支付(是否为缺省通用支付)'),
                'type'=>'radio',
                'options'=>array('0'=>app::get('ectools')->_('否'),'1'=>app::get('ectools')->_('是')),
            ),
            'status'=>array(
                'title'=>app::get('ectools')->_('是否开启此支付方式'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('ectools')->_('否'),'true'=>app::get('ectools')->_('是')),
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
        return app::get('ectools')->_('银联支付');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        $this->add_field('version','5.1.0');
        $this->add_field('encoding','utf-8');
        $this->add_field('txnType','01');
        $this->add_field('txnSubType','01');
        $this->add_field('bizType','000201');
        $this->add_field('frontUrl',$this->callback_url);
        $this->add_field('backUrl',$this->notify_url);
        $this->add_field('signMethod','01');
        $this->add_field('channelType','08');
        $this->add_field('accessType','0');
        $this->add_field('currencyCode','156');
        $this->add_field('merId',$this->getConf('mer_id', __CLASS__));//商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
        $this->add_field('orderId',$payment['payment_id']);//商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
        $this->add_field('txnTime',date('YmdHis'));//订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
        $this->add_field('txnAmt',number_format($payment['cur_money'],2,".","")*100);//交易金额，单位分，此处默认取demo演示页面传递的参数
        $this->add_field('payTimeout',date('YmdHis', strtotime('+15 minutes')));

        //'/mnt/hgfs/neigou/neigou_store/trunk/acp_test_sign.pfx'
        $certInfo = $this->getCertInfo($this->getConf('pfx_cert', __CLASS__),$this->getConf('pfx_pass', __CLASS__));
        $this->add_field('certId',$certInfo['serialNumber']);

        $params_str = $this->_create_link_string($this->fields,true,false);
        $params_sha256x16 = hash( 'sha256',$params_str);
        // 签名
        $result = openssl_sign ( $params_sha256x16, $signature, $certInfo['pkey'], 'sha256');
        if ($result) {
            $signature_base64 = base64_encode ( $signature );
            $params ['signature'] = $signature_base64;
        }

        $this->add_field('signature',$signature_base64);

        // Generate html and send payment.
        echo $this->get_html();
        exit;

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
     * 【新】支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
//        echo json_encode($recv);die;
        #键名与pay_setting中设置的一致
        $mer_id = $this->getConf('mer_id', __CLASS__);
        $mer_id = $mer_id == '' ? '777290058150258' : $mer_id;

        if($this->is_return_vaild($recv)){
            $ret['payment_id'] = $recv['orderId'];
            $ret['account'] = $mer_id;
            $ret['bank'] = app::get('ectools')->_('PC银联');//TODO 这里是支付方式名称 确认
            $ret['pay_account'] = $recv['accNo'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['settleAmt']/100;//TODO 确认是否是这个字段 清算金额
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['txnAmt']/100;
            $ret['trade_no'] = $recv['queryId'];//queryId 交易流水号 traceNo 系统跟踪号
            $ret['t_payed'] = strtotime($recv['traceTime']) ? strtotime($recv['traceTime']) : time();
            $ret['pay_app_id'] = "munionpay";
            $ret['pay_type'] = 'online';
            //$ret['memo'] = $recv['body'];
            if(intval($recv['respCode'])<1) {
                if($recv['respMsg']=='success'){
                    $ret['status'] = 'succ';
                } else {
                    $ret['status'] = 'failed';
                }
            }else{
                $ret['status'] =  'failed';
            }


        }else{
            $message = 'Invalid Sign';
            $ret['status'] = 'invalid';
        }

        return $ret;
    }

    public function gen_form(){
        return '';
    }

    /**
     * 【新】生成支付表单 - 自动提交
     * @params null
     * @return null
     */
    public function get_html() {
        $encodeType =  'utf-8';
        $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<body onload="javascript:document.pay_form.submit();">
    <form id="pay_form" name="pay_form" action="{$this->submit_url}" method="post">

eot;
        foreach ( $this->fields as $key => $value ) {
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
     * 生成签名
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return string
     */
    public function _get_mac($key){


    }

    /**
     * 获取证书ID
     * @param $cert_path 证书路径
     * @param $certPwd 证书密码
     * @return array|void
     */
    public function getCertInfo($cert_path, $certPwd){
        $pkcs12certdata = file_get_contents ( $cert_path );
        if($pkcs12certdata === false ){
            return;
        }
        if(openssl_pkcs12_read ( $pkcs12certdata, $certs, $certPwd ) == FALSE ){
            return;
        }
        $x509data = $certs ['cert'];
        if(!openssl_x509_read ( $x509data )){
        }
        $certdata = openssl_x509_parse ( $x509data );

        $certdata['pkey'] = $certs['pkey'];


        return  $certdata;
    }

    /**
     * 检验返回数据合法性
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return boolean
     */
    public function is_return_vaild($params) {
        $signature_str = $params ['signature'];
        unset ( $params ['signature'] );
        $params_str = $this->_create_link_string ( $params, true, false );
//        $logger->LogInfo ( '报文去[signature] key=val&串>' . $params_str );
//        $logger->LogInfo ( '签名原文>' . $signature_str );
        $strCert = $params['signPubKeyCert'];
//        $strCert = CertUtil::verifyAndGetVerifyCert($strCert);
        openssl_x509_read($strCert);
        $certInfo = openssl_x509_parse($strCert);
        $cn = $certInfo['subject'];
        $cn = $cn['CN'];
        $company = explode('@',$cn);
        if(count($company) < 3) {
            return null;
        }
        $cn = $company[2];
        if($cn!='中国银联股份有限公司' && "00040000:SIGN" != $cn) {
            return false;//cert owner is not cup $cn
        }
        $from = date_create ( '@' . $certInfo ['validFrom_time_t'] );
        $to = date_create ( '@' . $certInfo ['validTo_time_t'] );
        $now = date_create ( date ( 'Ymd' ) );
        $interval1 = $from->diff ( $now );
        $interval2 = $now->diff ( $to );
        if ($interval1->invert || $interval2->invert) {
//            $logger->LogInfo("signPubKeyCert has expired");
            return null;
        }
//        $result = openssl_x509_checkpurpose($strCert, X509_PURPOSE_ANY, array('/mnt/hgfs/neigou/neigou_store/trunk/acp_test_root.cer', '/mnt/hgfs/neigou/neigou_store/trunk/acp_test_middle.cer'));
        $result = openssl_x509_checkpurpose($strCert, X509_PURPOSE_ANY, array($this->getConf('root_cert', __CLASS__), $this->getConf('middle_cert', __CLASS__)));
        if($result === FALSE){
//            $logger->LogInfo("validate signPubKeyCert by rootCert failed");
            return null;
        } else if($result === TRUE){

            $params_sha256x16 = hash('sha256', $params_str);
//            $logger->LogInfo ( 'sha256>' . $params_sha256x16 );
            $signature = base64_decode ( $signature_str );
            $isSuccess = openssl_verify ( $params_sha256x16, $signature,$strCert, "sha256" );

            if($isSuccess){
                return true;
            } else {
                return false;
            }
        } else {
//            $logger->LogInfo("validate signPubKeyCert by rootCert failed with error");
            return null;
        }
        return false;
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
            $linkString .= $key . "=" . $value . "&";
        }

        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
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
