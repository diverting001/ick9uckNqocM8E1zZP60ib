<?php

/**
 * 招商银行 支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2018/08/06
 * Time: 11:12
 */
final class wap_payment_plugin_mcmbchina extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '招商银行 支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '招商银行 支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mcmbchina';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mcmbchina';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '招商银行 支付';
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
//    public $is_general = 1;

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
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mcmbchina_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mcmbchina', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->callback_url, $matches)) {
            $this->callback_url = str_replace('http://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "http://" . $this->callback_url;
        } else {
            $this->callback_url = str_replace('https://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "https://" . $this->callback_url;
        }
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
        return '招商银行 支付配置信息';
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
            'app_id'=>array(
                'title'=>app::get('ectools')->_('商户AppID'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'sign_key'=>array(
                'title'=>app::get('ectools')->_('sign_key'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('统一下单地址'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'refund_url'=>array(
                'title'=>app::get('ectools')->_('退款URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'refund_query_url'=>array(
                'title'=>app::get('ectools')->_('退款查询URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'order_by' =>array(
                'title'=>app::get('ectools')->_('排序'),
                'type'=>'string',
                'label'=>app::get('ectools')->_('整数值越小,显示越靠前,默认值为1'),
            ),
            'pay_type'=>array(
                'title'=>app::get('wap')->_('支付类型(是否在线支付)'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('wap')->_('否'),'true'=>app::get('wap')->_('是')),
                'name' => 'pay_type',
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
        return app::get('ectools')->_('招商银行 支付配置信息');
    }



    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        $this->add_field('funcode','WP001');//定值：WP001
        $this->add_field('version','1.0.0');//定值：1.0.0
        $this->add_field('appId',$this->getConf('app_id',__CLASS__));//商户应用唯一标识
        $this->add_field('mhtOrderNo',$payment['payment_id']);//商户订单号
        $this->add_field('mhtOrderName','内购网');//商户商品名称 TODO 确认此参数和orderDetail参数表示的内容是否一致
        $this->add_field('mhtOrderType','05');//商户交易类型 05 代理消费
        $this->add_field('mhtCurrencyType','156');//商户订单币种类型 156人民币
        $this->add_field('mhtOrderAmt',number_format($payment['cur_money'],2,".","")*100);//商户订单交易金额 单位(人民币)：分 整数，无小数点
        $this->add_field('mhtOrderDetail','内购网');//商户订单详情

        $expire_time = 2380;
        $spend = time()-$payment['create_time'];
        $expire = $expire_time-$spend;
        if($expire>60){
            $this->add_field('mhtOrderTimeOut',$expire);//商户订单超时时间 60~3600秒，默认3600 TODO 确认只能是最小60？
        } else {
            $this->add_field('mhtOrderTimeOut',60);//商户订单超时时间 60~3600秒，默认3600
        }
        $this->add_field('mhtOrderStartTime',date('YmdHis',$payment['create_time']));//商户订单开始时间 yyyyMMddHHmmss
        $this->add_field('notifyUrl',$this->notify_url);//HTTP协议或者HTTPS协议，POST方式提交报文。
        $this->add_field('frontNotifyUrl',$this->callback_url);//HTTP协议或者HTTPS协议，POST方式提交报文。outputType=0时必填
        $this->add_field('mhtCharset','UTF-8');//商户字符编码
        $this->add_field('deviceType','0600');//0600公众号
        $this->add_field('payChannelType','13');//用户所选渠道类型 12 支付宝 13 微信 25 手机QQ (仅支持outputType=0) 见附录6.5渠道类型表
//        $this->add_field('mhtReserved','0600');//商户保留域
        $this->add_field('outputType','1');//0 直接调起支付 1 返回支付凭证
        $this->add_field('mhtSubAppId','wxf3249ddedebd8b4d');//outputType=1且paychannelType=13时必传
        $this->add_field('consumerId',$payment['wxopenid']);//0表示支付不限制卡类型 1表示不能使用信用卡支付
        $this->add_field('mhtLimitPay','0');//0表示支付不限制卡类型 1表示不能使用信用卡支付
//        $this->add_field('mhtGoodsTag','0600');//用于营销活动
        $this->add_field('mhtSignType','MD5');//定值：MD5
        $this->add_field('mhtSignature',$this->sign());//签名
        $url = $this->getConf('submit_url',__CLASS__);

        $rzt = $this->request($url,$this->fields);
//        $rzt = 'funcode=WP001&signature=fc14dce7247f9b076f0d6a76d9efe781&responseTime=20190423101320&mhtOrderNo=1555985597633644&appId=155591605514381&signType=MD5&nowPayOrderNo=2004131201904231013200061424&tn=timeStamp%3D1555985600%26nonceStr%3Do6vUoBC6CVAmXYnOT1vzh4Kp3z5W9GSD%26prepay_id%3Dwx2310132037776588125411b62535797670%26wxAppId%3Dwxf3249ddedebd8b4d%26paySign%3De8bhto4ZJf2eGB4IO%2BX3Ua417U2G19DAUxgM6gvarnsC%2BxrDPgsqGzwZu9NuU4axF%2FCTgEuBFDM%2F0vWwiMOHDL95Ztcu5D5QSq%2FHOPN8hn2dcsBd40QuL%2FCUxY1XNAGHazj3tvGc8VZMe0DEbwHgkPYlt2LSgeSC52VB5wK%2Fum6Zqy7A1hEt9z7jkNIAwEdtz%2BtJQbXLpK8IAdfqFwNoSVgay5LA88OlV67aEulxL7qMFY%2FvYN0slwQls7DOrq%2BLTCtHUxyua8c%2Fsd5mccvFSyT2vddJHCXG8v%2BmjBoNExcBbSxHPEQTVe8puFGBUasQ8mKlY6XM%2FQCNZV4TYAZNvw%3D%3D%26signType%3DRSA&version=1.0.0&responseCode=A001&mhtSubMchId=000000010001209&responseMsg=E000%23%E6%88%90%E5%8A%9F%5B%E6%88%90%E5%8A%9F%5D';
        parse_str($rzt,$query_arr);
        if($query_arr && $query_arr['responseCode']=='A001') {
            //发起微信支付
            $tn = $query_arr['tn'];
            parse_str(urldecode($tn),$tnA);
            $nativeObj["appId"] = $tnA['wxAppId'];
            $nativeObj["package"] = 'prepay_id='.$tnA['prepay_id'];
            $nativeObj["timeStamp"] = $tnA['timeStamp'];
            $nativeObj["nonceStr"] = $tnA['nonceStr'];
            $nativeObj["signType"] = $tnA['signType'];
            //针对sign处理
            $tn_decode = rawurldecode($tn);
            $tmpA = explode('&',$tn_decode);
            foreach ($tmpA as $key=>$tnV){
                $tmpI = explode('=',$tnV);
                if($tmpI[0] == 'paySign'){
                    $key_id = $key;
                    continue;
                }
            }
            $sign = substr($tmpA[$key_id],8);
            $nativeObj["paySign"] = $sign;

            echo $this->gen_html(json_encode($nativeObj),$payment['order_id']);
        } else {
            //跳转支付失败页面
            $url = app::get('wap')->router()->gen_url(array('app'=>'b2c','ctl'=>'wap_paycenter2','act'=>'result_failed','arg0'=>$payment['order_id']));
            //redirect to pay err page
            \Neigou\Logger::General('pay.mcmbchina', array('action' => 'req fail', 'result' => $rzt));
            header('Location: '.$url);die;
        }



        die;
    }

    /**
     * 支付结果人工查询
     */
    public function query() {
        $this->add_field('funcode',           'MQ002');
        $this->add_field('version',           '1.0.0');
        $this->add_field('deviceType',        '0600');
        $this->add_field('appId',              $this->getConf('app_id',__CLASS__));
        $this->add_field('mhtOrderNo',         $_GET['payment_id']);
        $this->add_field('mhtCharset',         'UTF-8');
        $this->add_field('mhtSignType',        'MD5');
        $this->add_field('mhtSignature',       $this->sign());

        $rzt = $this->request($this->getConf('submit_url',__CLASS__),$this->fields);
        parse_str($rzt,$data);
        print_r($data);
    }

    public function _create_link_string($para, $sort, $encode){
        if($para == NULL || !is_array($para))
            return "";
        $linkString = "";
        if($sort){
            ksort ( $para );
            reset ( $para );
        }
        foreach ($para as $key=>$value) {
            if($encode) {
                $value = urlencode($value);
            }
            $linkString .=$key.'='.$value.'&';
        }
        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
        return $linkString;
    }

    public function sign() {
        $str = $this->_create_link_string($this->fields,true,false);
        $str.='&'.md5($this->getConf('sign_key',__CLASS__));
        return md5($str);
    }

    /**
     * 没有前端回调
     * @param $recv
     * @return mixed
     */
    public function callback(&$recv) {
        return false;
    }

    public function request($url = '', $post_data = array()) {
        $curl = new \Neigou\Curl();
        //TODO 正式环境打开代理请求
        $proxyServer = NEIGOU_HTTP_PROXY;
        $opt_config = array(
//            CURLOPT_POST => true,
            CURLOPT_FAILONERROR => true,
//            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
            CURLOPT_PROXY => $proxyServer,
        );
        $curl->SetOpt($opt_config);
//        $curl->SetHeader('Content-Type', 'application/json');
        $result = $curl->Post($url, $post_data);
        \Neigou\Logger::General('pay.mcmbchina', array('action' => 'req', 'opt_config' => $opt_config,'req_url'=>$url,'post_data'=>$post_data,'response_data'=>$result));
//        $resultData = json_decode($result, true);
        return $result;
    }

    /**
     * 校验方法
     * @param null
     * @return boolean
     */
    public function is_fields_valiad(){
        return true;
    }

    public function is_return_vaild($data){
        $sign = $data['sign'];
        unset($data['sign']);
        if($sign==md5($data['trade_no'].$this->getConf('sign_key',__CLASS__).$data['cur_money'].$data['credit_id'])){
            return true;
        } else {
            \Neigou\Logger::General('ecstore.callback.mcmbchina.sign_err',array('remark'=>'sign_err','data'=>$data));
            return false;
        }
    }

    protected function gen_html($native,$order_id){
        // 微信提交支付,调用微信内置js
        $title = '内购网';
        $success_url = app::get('wap')->router()->gen_url(array('app'=> 'b2c','ctl'=>'wap_paycenter2','act'=>'result_wait','full'=>1,'arg0'=>$order_id,'arg1'=>'true'));
        $failure_url = app::get('wap')->router()->gen_url(array('app'=> 'b2c','ctl'=>'wap_paycenter2','act'=>'result_failed','full'=>1,'arg0'=>$order_id,'arg1'=>'result_placeholder'));

        $strHtml = '<html>
        		   <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
        		   <title>' . $title . '订单支付</title>
                    <script language="javascript">
                    function call_pay() {
                        WeixinJSBridge.invoke(
					                "getBrandWCPayRequest",
					                '. $native .',
					                function(res){
					                    if(res.err_msg == "get_brand_wcpay_request:ok"){
			                                window.location.href = "' . $success_url . '";
			                            }else{
			                                //alert("支付失败，请重新支付，或联系客服：400-6666-365");
                                            failure_url = "' . $failure_url . '";
                                            failure_url = failure_url.replace(/result_placeholder/, encodeURIComponent(res.err_msg));
			                                window.location.href = failure_url;
			                            }
					                }
					            );
                    }
                    // 当微信内置浏览器完成内部初始化后会触发WeixinJSBridgeReady事件。
                    if (typeof WeixinJSBridge == "undefined"){
					   if( document.addEventListener ){
					       document.addEventListener("WeixinJSBridgeReady", call_pay, false);
					   }else if (document.attachEvent){
					       document.attachEvent("WeixinJSBridgeReady", call_pay);
					       document.attachEvent("onWeixinJSBridgeReady", call_pay);
					   }
					}else{
					   call_pay();
					}

                    </script>
                    <body>
                    <button type="button" id="btn_pay" onclick="call_pay()" style="display:none;">微信支付</button>
			        <script>
					    document.getElementById("btn_pay").click();
					</script>
                    </body>
                    </html>';

        return $strHtml;
    }

    public function gen_form(){
        return '';
    }
}