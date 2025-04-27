<?php

/**
 * 嘉宝微信支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2017/12/28
 * Time: 15:25
 */
final class wap_payment_plugin_mjbweixin extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '嘉宝微信支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '嘉宝微信支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mjbweixin';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mjbweixin';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '嘉宝微信支付';
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
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
//        echo 2;die;
        parent::__construct($app);
        $this->notify_url = kernel::openapi_url('openapi.weixin', 'mjbwxpay');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mjbweixin', 'callback');
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
        $this->signtype = 'MD5';
        $this->app_id = trim($this->getConf('appId', 'weixin_payment_plugin_wxpay')); // appid 公众号 ID
        $this->submit_charset = 'utf-8';
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        return '嘉宝微信支付配置信息';
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
                'title'=>app::get('ectools')->_('商户号'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'app_id'=>array(
                'title'=>app::get('ectools')->_('App ID'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'sign_key'=>array(
                'title'=>app::get('ectools')->_('sign_key'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('订单提交URL'),
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
        return app::get('ectools')->_('雪松支付');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        //组合微信支付所需要的参数
        $appId = trim($this->getConf('app_id', __CLASS__)); // appid 公众号 ID
        $paySignKey = trim($this->getConf('sign_key', __CLASS__)); // PaySignKey 对应亍支付场景中的 appKey 值
        $mer_id = trim($this->getConf('mer_id', __CLASS__)); // 商户ID
        $price = ceil($payment['cur_money'] * 100);
        $this->add_field('mch_id',$mer_id);
        $this->add_field('body','嘉宝精选');
        $this->add_field('out_trade_no',$payment['payment_id']);
        $this->add_field('total_fee',$price);
        $this->add_field('spbill_create_ip',strval( $payment['ip'] ));
        $spend = time()-$payment['create_time'];
        $expire = 2380-$spend;

        $this->add_field('time_expire',date('YmdHis',time()+$expire));//交易截止时间 TODO
        $this->add_field('notify_url',$this->notify_url);
        $this->add_field('trade_type','APP');
        $prepay_id = $this->get_prepay_id($appId,$paySignKey);
        $nativeObj["appid"] = $appId;
        $nativeObj["partnerid"] = $mer_id;
        $nativeObj["prepayid"] = $prepay_id;
        $nativeObj["noncestr"] = weixin_commonUtil::create_noncestr();
        $nativeObj["package"] = 'Sign=WXPay';
        $nativeObj["timestamp"] = strval(time());
        $nativeObj["sign"] = $this->get_biz_sign_md5($nativeObj, $paySignKey);
        $this->callback_url .= '?order_sn='.$payment['payment_id'];
        echo $this->get_html($nativeObj);exit;
    }

    private function get_prepay_id($appId,$paySignKey){
        $prepay_id = weixin_commonUtil::getPrepayId($appId,$paySignKey,$this->fields);
        if ($prepay_id =='' ) {
            $prepay_id = weixin_commonUtil::getPrepayId($appId,$paySignKey,$this->fields);
        }
        return $prepay_id;
    }


    private function get_biz_sign_md5($bizObj, $paySignKey) {

        try {
            $bizString = $this->_create_link_string($bizObj,true,false);
            $bizString .="&key=".$paySignKey;
            return strtoupper(md5($bizString));
        } catch (Exception $e) {
            die($e->getMessage());
        }
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
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    function post_callback(&$in){
        $paySignKey = trim($this->getConf('sign_key', __CLASS__)); // PaySignKey 对应亍支付场景中的 appKey 值
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
        $ret['pay_app_id'] = "mjbweixin";
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
     * 校验方法
     * @param null
     * @return boolean
     */
    public function is_fields_valiad(){
        return true;
    }







    public function gen_form(){
        return '';
    }



    /**
     * 【新】生成支付表单 - 自动提交
     * @params null
     * @return null
     *
     */
    public function get_html($data) {
        $encodeType =  'utf-8';

        if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
            $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<script>
window.AndroidWebView.callWechatPay('{$data["appid"]}','{$data["partnerid"]}','{$data["prepayid"]}','{$data["noncestr"]}','{$data["package"]}','{$data["timestamp"]}','{$data["sign"]}','{$this->callback_url}');
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
    if(window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.callWechatPay&& window.webkit.messageHandlers.callWechatPay.postMessage){
            window.webkit.messageHandlers.callWechatPay.postMessage(['{$data["appid"]}','{$data["partnerid"]}','{$data["prepayid"]}','{$data["noncestr"]}','{$data["package"]}','{$data["timestamp"]}','{$data["sign"]}','{$this->callback_url}'])
        }else{
            callWechatPay('{$data["appid"]}','{$data["partnerid"]}','{$data["prepayid"]}','{$data["noncestr"]}','{$data["package"]}','{$data["timestamp"]}','{$data["sign"]}','{$this->callback_url}')
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
                $linkString .= $key  .'='. $value .'&';
            }
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