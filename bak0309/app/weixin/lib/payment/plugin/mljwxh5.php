<?php
final class weixin_payment_plugin_mljwxh5 extends ectools_payment_app implements ectools_interface_payment_app {

    /**
     * @var string 支付方式名称
     */
    public $name = '微信支付H5';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '微信支付H5';
     /**
     * @var string 支付方式key
     */
    public $app_key = 'mljwxh5';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mljwxh5';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '微信支付H5';
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
     * @var array 展示的环境[h5 weixin wxwork]
     */
    public $display_env = array('h5');

    /**
     * @var string 通用支付
     */
    public $is_general = 0;

    /**
     * @var array 扩展参数
     */
    public $supportCurrency = array("CNY"=>"01");

    public $restriction = array('LJ_INSURE');

    public $order_query_url = 'https://api.mch.weixin.qq.com/pay/orderquery';

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);
         $this->notify_url = kernel::openapi_url('openapi.weixin', 'mljwxh5');
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
        $this->submit_charset = 'UTF-8';
        $this->signtype = 'MD5';
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        return '微信支付 H5';
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
                'title'=>app::get('weixin')->_('appId'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
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
        return '微信支付h5';
    }

    /**
     * 获取客户端IP
     * @return string
     */
    function get_client_ip() {
        if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return preg_match ( '/[\d\.]{7,15}/', $ip, $matches ) ? $matches [0] : '';
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        $appId =  $this->getConf('appId',__CLASS__);
        $paySignKey =  $this->getConf('paySignKey',__CLASS__);
        $data['appid'] = $appId;
        $data['mch_id'] = $this->getConf('partnerId',__CLASS__);
        $nonce_str = weixin_commonUtil::create_noncestr();
        $data['nonce_str'] = $nonce_str;
        $data['body'] = strval( str_replace(' ', '', (isset($payment['body']) && $payment['body']) ? $payment['body'] : app::get('weixin')->_('内购网订单') ) );
        $data['out_trade_no'] = strval( $payment['payment_id'] );
        $data['total_fee'] = strval( bcmul($payment['cur_money'], 100) );
        $data['spbill_create_ip'] = $this->get_client_ip();
        $data['notify_url'] = $this->notify_url;
        $data['trade_type'] = 'MWEB';
        $prepay_info = weixin_commonUtil::getPrepayInfo($appId,$paySignKey,$data);
        if($prepay_info['return_code']=='SUCCESS'){
//            echo $this->js_submit($prepay_info['mweb_url']);
//            exit();
            header('Location:'.$prepay_info['mweb_url']);
        } else {
            die("获取统一支付prepay_id错误");
        }
    }


    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    function callback(&$in){
        $paySignKey = trim($this->getConf('paySignKey', __CLASS__)); // PaySignKey 对应亍支付场景中的 appKey 值
        $postData = $in['weixin_postdata'];
        unset($in['weixin_postdata']);
        ksort($postData);
        $unSignParaString = weixin_commonUtil::formatQueryParaMap($postData, false);
        $checksign = weixin_commonUtil::verifySignature($unSignParaString, $postData['sign'], $paySignKey);
        $objMath = kernel::single('ectools_math');
        $ret = array();
        $ret['payment_id'] = $postData['out_trade_no'];
        $ret['account'] = $postData['openid'];
        $ret['bank'] = app::get('weixin')->_('微信H5支付').$postData['bank_type'];
        $ret['pay_account'] = app::get('weixin')->_('付款帐号');
        $ret['currency'] = 'CNY';
        $ret['money'] = $objMath->number_multiple(array($postData['total_fee'], 0.01));
        $ret['paycost'] = '0.000';
        $ret['cur_money'] = $objMath->number_multiple(array($postData['total_fee'], 0.01));
        $ret['trade_no'] = $postData['transaction_id'];
        $ret['t_payed'] = strtotime($postData['time_end']);
        $ret['pay_app_id'] = "mljwxh5";
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

    protected function get_html(){

    }

    public function js_submit($url){
        $html = '
<html>
    <body>
        <script type="text/javascript" src="/app/jifen/statics/wap/js/libs/jquery.min.js"></script>
        <script>
            $.get("/mwxh5_check");
            setTimeout(function() {
              window.location.href="'.$url.'"
            },1000);
        </script>
    </body>
</html>
';
        return $html;

    }

    /**
     * 交易查询,对账
     * @param $qurey_data
     * @return bool
     */
    public function trade_query($qurey_data)
    {
        $appId      = trim($this->getConf('appId',      __CLASS__)); // appid 公众号 ID
        $paySignKey = trim($this->getConf('paySignKey', __CLASS__)); // PaySignKey 对应亍支付场景中的 appKey 值
        $partnerId  = trim($this->getConf('partnerId',  __CLASS__)); // 商户ID

        $nativeObj["appid"] = $appId;
        $nativeObj["mch_id"] = $partnerId;

        $nativeObj["out_trade_no"] = $qurey_data['out_trade_no'];
        $nativeObj["nonce_str"] = weixin_commonUtil::create_noncestr();

        ksort($nativeObj);
        $paramstr = weixin_commonUtil::formatBizQueryParaMap($nativeObj,false);

        $nativeObj["sign"] = weixin_commonUtil::sign($paramstr, $paySignKey);

        $xml = weixin_commonUtil::arrayToXml($nativeObj);

        try {
            $value = weixin_commonUtil::postXmlCurl($xml, $this->order_query_url, 10);
        }catch (Exception $e) {
            return false;
        }

        $ret = weixin_commonUtil::xmlToArray($value);
        //echo"<pre>";print_r($ret);exit;
        if('SUCCESS' != $ret['return_code'])
        {
            return false;
        }

        if('SUCCESS' != $ret['result_code'])
        {
            return false;
        }
        //trade_state描述 SUCCESS—支付成功 REFUND—转入退款 NOTPAY—未支付 CLOSED—已关闭 REVOKED—已撤销（刷卡支付）USERPAYING--用户支付中PAYERROR--支付失败(其他原因，如银行返回失败)
        $payed_trade_state = array('SUCCESS', 'REFUND');
        if( in_array($ret['trade_state'], $payed_trade_state))//交易支付成功或交易结束，不可退款
        {
            $data['total_fee'] = $ret['total_fee']/100;
            $data['trade_no']  = $ret['transaction_id'];
            $data['payed'] = 'succ';

            return $data;
        }
        return false;
    }
}
